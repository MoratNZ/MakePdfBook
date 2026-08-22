#!/usr/bin/env python3
"""
Render a MakePdfBook Book into a PDF via WeasyPrint.

Invoked by MakePdfBook::render() (src/MakePdfBook.php) with a temp directory
already populated by Book::writeContent() (src/Book.php):
    book.json       - Book::jsonSerialize() - title, titlepage, chapters
    titlepage.html  - present iff book.titlepage is set
    chapter-N.html  - one per chapter, 1-indexed, in book.json order

Structural HTML fixes (image sizing, table headers, id/attribute rewrites,
extraction) are done via lxml.html on a parsed tree.

Usage:
    render_pdf_book.py <temp_dir> <output_pdf> <draft: true|false>
"""
import argparse
import json
import logging
import os
import re
import sys
from datetime import datetime
from html import escape as escape_html

import add_link_borders
import lxml.html
import weasyprint
from lxml.html import HtmlElement

HERE = os.path.dirname(os.path.abspath(__file__))
STYLES_DIR = os.path.join(HERE, "..", "resources", "ext.MakePdfBook", "styles")
IMAGES_DIR = os.path.join(HERE, "..", "resources", "ext.MakePdfBook", "images")


class _CollectingHandler(logging.Handler):
    # logging.Handler requires subclassing to intercept records
    def __init__(self) -> None:
        super().__init__(level=logging.WARNING)
        self.records: list[str] = []

    def emit(self, record: logging.LogRecord) -> None:
        self.records.append(self.format(record))


def parse_html_fragment(raw: str) -> HtmlElement:
    return lxml.html.fromstring(raw, parser=lxml.html.HTMLParser(remove_comments=True))


def smarten_quotes(tree: HtmlElement) -> None:
    """Classify each '"' by what actually precedes it (opening after
    whitespace/an opening bracket/start of document, closing otherwise);
    dumb pair breaks on e.g. inch markers.
    """
    OPENING_QUOTE_RE = re.compile(r'(?<=[\s([{])"')
    prev_char = " "  # start-of-document counts as an opening boundary

    def process(text):
        nonlocal prev_char
        if not text:
            return text
        opened = OPENING_QUOTE_RE.sub('“', prev_char + text)[1:]
        prev_char = text[-1]
        return opened.replace('"', '”')

    def walk(el):
        el.text = process(el.text)
        for child in el:
            walk(child)
            child.tail = process(child.tail)

    walk(tree)


def fix_svg_img_sizing(tree: HtmlElement) -> None:
    """WeasyPrint doesn't honour <img>'s width/height attributes for SVGs,
    it falls back toward the SVG's own intrinsic size.
    """
    for img in tree.iter("img"):
        w, h = img.get("width"), img.get("height")
        style = img.get("style") or ""
        if w and h and not re.search(r'\b(?:width|height)\s*:', style):
            sep = "" if not style or style.rstrip().endswith(";") else ";"
            img.set("style", f"{style}{sep}width:{w}px;height:{h}px;")


def fix_table_headers(tree: HtmlElement) -> None:
    """MediaWiki's table markup doesn't have a <thead> - the header row
    is just an ordinary <tr> of <th> cells inside <tbody>. Without a real
    <thead>, a table that breaks across a page boundary silently loses its
    column headers on continuation pages. Promote a leading th-only row.
    """
    for tbody in tree.iter("tbody"):
        first_row = tbody.find("tr")
        if first_row is None or len(first_row) == 0 or any(cell.tag != "th" for cell in first_row):
            continue
        thead = tree.makeelement("thead")
        tbody.remove(first_row)
        thead.append(first_row)
        tbody.addprevious(thead)


def apply_magic_words(html: str) -> str:
    """%startCenter%/%vspace%N%/etc -> convert to html (should probably move wiki-side eventually)"""
    MARKER_RE = r"%(?:startCenter|endCenter|startHuge|endHuge|vspace%\d+|pageBreak)%"
    MARKER_ONLY_RE = re.compile(r"(\s|" + MARKER_RE + r")*", flags=re.I)

    html = re.sub(
        r'<p[^>]*>(.*?)</p>',
        lambda m: m.group(1) if MARKER_ONLY_RE.fullmatch(m.group(1)) else m.group(0),
        html, flags=re.S,
    )

    html = re.sub(r'%startCenter%', '<div class="center">', html, flags=re.I)
    html = re.sub(r'%endCenter%', '</div>', html, flags=re.I)
    html = re.sub(r'%startHuge%', '<div class="huge">', html, flags=re.I)
    html = re.sub(r'%endHuge%', '</div>', html, flags=re.I)
    # Capped at 20mm: large vspace values can overflow onto a near-blank second page.
    html = re.sub(r'%vspace%(\d+)%', lambda m: f'<div class="vspace" style="height:{min(int(m.group(1)), 20)}mm"></div>', html, flags=re.I)
    html = re.sub(r'%pageBreak%', '<div class="pagebreak"></div>', html, flags=re.I)
    return html


def render_tree(tree: HtmlElement) -> str:
    smarten_quotes(tree)
    return apply_magic_words(lxml.html.tostring(tree, encoding="unicode"))


def find_mw_headline(el: HtmlElement) -> HtmlElement | None:
    return el.find('.//span[@class="mw-headline"]')


def headline_spans(tree: HtmlElement):
    """Every mw-headline span in tree that has an id - the anchors internal
    wiki links can target."""
    return (span for span in tree.iter("span") if span.get("class") == "mw-headline" and span.get("id"))


def extract_subsections(tree: HtmlElement) -> list[tuple[str, str]]:
    result = []
    for h2 in tree.iter("h2"):
        span = find_mw_headline(h2)
        if span is not None and span.get("id"):
            result.append((span.text_content().strip(), span.get("id")))
        else:
            print(f"WARNING: h2 heading has no linkable id, omitting from ToC: {h2.text_content().strip()!r}", file=sys.stderr)
    return result


def build_stylesheet(is_draft: bool, today: str) -> str:
    with open(os.path.join(STYLES_DIR, "MakePdfBook.css"), encoding="utf-8") as f:
        base_css = f.read().replace("min-height: 75vh;", "min-height: 600px;") # weasyprint chokes on 75vh, doesn't like units
    with open(os.path.join(STYLES_DIR, "MakePdfBookPrint.css"), encoding="utf-8") as f:
        print_css = f.read().replace("__PDF_GENERATED_DATE__", today)
    css = base_css + "\n" + print_css
    if is_draft:
        watermark_path = os.path.join(IMAGES_DIR, "draft-watermark.svg")
        WATERMARK_BACKGROUND_SIZE = "990pt 990pt"
        # Watermark must be the last thing in <body>.
        css += f"""
@page {{
  @bottom-center {{ content: "Draft PDF generated: {today}"; }}
}}
.draft-watermark {{
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: url("{watermark_path}");
  background-repeat: no-repeat;
  background-position: center;
  background-size: {WATERMARK_BACKGROUND_SIZE};
}}
"""
    return css


def build_toc_as_li(css_class: str, link_class: str, anchor: str, text: str) -> str:
    """Build via lxml, not an f-string, so text gets escaped like other content."""
    li = lxml.html.Element("li", {"class": css_class})
    a = lxml.html.Element("a", {"class": link_class, "href": f"#{anchor}"})
    a.text = text
    li.append(a)
    return lxml.html.tostring(li, encoding="unicode")


def process_titlepage(temp_dir: str, book: dict) -> tuple[str, list[str]]:
    titlepage_path = os.path.join(temp_dir, "titlepage.html")
    if not (book.get("titlepage") and os.path.exists(titlepage_path)):
        return "", []

    with open(titlepage_path, encoding="utf-8") as f:
        raw = f.read()

    tree = parse_html_fragment(raw)
    subsections = extract_subsections(tree)
    fix_svg_img_sizing(tree)
    fix_table_headers(tree)

    html = render_tree(tree)

    # "toc-entry-plain": same size/indent as a chapter entry but regular
    # weight with a dot leader - distinct from both chapter (bold) and
    # subsection (indented, smaller) styling.
    toc_entries = [
        build_toc_as_li("toc-entry-plain", "toc-link", anchor_id, title)
        for title, anchor_id in subsections
    ]

    return html, toc_entries


def wiki_link_target(href: str) -> str | None:
    """The mw-headline id an internal wiki link points at - or None for anything else (external URL, redlink query string)."""
    if href.startswith("#"):
        return href[1:] or None
    m = re.match(r"^/index\.php/([^#?]+)(?:#(.+))?$", href)
    if not m:
        return None
    return m.group(2) or m.group(1).split(":", 1)[-1]


def resolve_internal_links(html: str) -> str:
    """Rewrite <a href> that target another page/heading in this same book
    into an in-document link instead of an external URL
    """
    tree = lxml.html.document_fromstring(html)
    id_map = {span.get("data-original-id", span.get("id")): span.get("id") for span in headline_spans(tree)}
    for link in tree.iter("a"):
        target_id = wiki_link_target(link.get("href", ""))
        if target_id and target_id in id_map:
            link.set("href", f"#{id_map[target_id]}")
    return "<!DOCTYPE html>\n" + lxml.html.tostring(tree, encoding="unicode")


def process_chapter(temp_dir: str, n: int, chapter: dict) -> tuple[str, str]:
    chapter_path = os.path.join(temp_dir, f"chapter-{n}.html")
    with open(chapter_path, encoding="utf-8") as f:
        raw = f.read()
    tree = parse_html_fragment(raw)

    cid = f"chapter-{n}"
    subsections = extract_subsections(tree)

    rules_div = tree.find('.//div[@class="rulesNumbering"]')
    chapter_num_match = re.search(r'counter-reset:\s*page\s+(\d+)', rules_div.get("style", "")) if rules_div is not None else None
    if chapter_num_match:
        chapter_num = int(chapter_num_match.group(1))
    else:
        print(f"WARNING: no rulesNumbering marker found, falling back to position ({n})", file=sys.stderr)
        chapter_num = n

    h1 = tree.find(".//h1")
    h1_span = find_mw_headline(h1) if h1 is not None else None
    label = h1_span.text_content().strip() if h1_span is not None else chapter["title"]

    if rules_div is not None:
        rules_div.set("style", f"counter-reset: bookChapter {chapter_num}")

    fix_svg_img_sizing(tree)
    fix_table_headers(tree)

    # MediaWiki only guarantees mw-headline ids are unique per page - once
    # chapters are concatenated, e.g. two chapters' h3 "General" collide
    # unless every heading level (not just the h2s with a ToC entry) is
    # prefixed here. The original id is kept in data-original-id so
    # resolve_internal_links() can later match wiki links against it.
    for span in list(headline_spans(tree)):
        old_id = span.get("id")
        span.set("data-original-id", old_id)
        span.set("id", f"{cid}-{old_id}")

    content = render_tree(tree)
    html = f'<section class="chapter" id="{cid}">\n{content}\n</section>'

    sub_toc = ""
    if subsections:
        sub_items = "\n".join(
            build_toc_as_li("toc-subentry", "toc-link toc-sublink", f"{cid}-{subsection_id}", f"{chapter_num}.{sub_num} {subsection_title}")
            for sub_num, (subsection_title, subsection_id) in enumerate(subsections, start=1)
        )
        sub_toc = f'<ol class="toc-sublist">\n{sub_items}\n</ol>'

    toc_entry = build_toc_as_li("toc-entry", "toc-link", cid, f"{chapter_num} {label}") + sub_toc

    return html, toc_entry


def build_combined_html(book: dict, css: str, titlepage_html: str, toc_entries: list[str], chapters_html: list[str], is_draft: bool) -> str:
    # The watermark must be the last thing in <body> to paint on top of everything else
    watermark_html = '<div class="draft-watermark"></div>' if is_draft else ""
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{escape_html(book["title"])}</title>
<style>
{css}
</style>
</head>
<body>

<section class="titlepage">
{titlepage_html}
</section>

<nav id="toc">
<h1>Contents</h1>
<ol>
{chr(10).join(toc_entries)}
</ol>
</nav>

{chr(10).join(chapters_html)}

{watermark_html}
</body>
</html>
"""


if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("temp_dir")
    ap.add_argument("output_pdf")
    ap.add_argument("draft", choices=["true", "false"])
    args = ap.parse_args()

    with open(os.path.join(args.temp_dir, "book.json"), encoding="utf-8") as f:
        book = json.load(f)

    titlepage_html, toc_entries = process_titlepage(args.temp_dir, book)

    chapters_html = []
    for n, chapter in enumerate(book["chapters"], start=1):
        chapter_html, toc_entry = process_chapter(args.temp_dir, n, chapter)
        chapters_html.append(chapter_html)
        toc_entries.append(toc_entry)

    is_draft = args.draft == "true"
    today = datetime.now().strftime("%B %d, %Y")
    css = build_stylesheet(is_draft, today)
    final_html = resolve_internal_links(build_combined_html(book, css, titlepage_html, toc_entries, chapters_html, is_draft))

    final_html_path = os.path.join(args.temp_dir, "final.html")
    with open(final_html_path, "w", encoding="utf-8") as f:
        f.write(final_html)

    raw_pdf_path = os.path.join(args.temp_dir, "raw.pdf")
    # WeasyPrint reports broken images/fonts/CSS via logging, not exceptions -
    # collect anything at WARNING+ so we still fails loudly on warnings (PHP's caller only checks our exit code).
    log_handler = _CollectingHandler()
    logging.getLogger("weasyprint").addHandler(log_handler)

    weasyprint.HTML(filename=final_html_path, base_url="file:///").write_pdf(raw_pdf_path)

    if log_handler.records:
        raise RuntimeError("WeasyPrint reported problems:\n" + "\n".join(log_handler.records))

    add_link_borders.write_pdf(raw_pdf_path, args.output_pdf)
