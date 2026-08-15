#!/usr/bin/env python3
"""
Post-process a WeasyPrint-rendered PDF to add hyperref's default link
appearance: a thin coloured box around link areas (red for internal
cross-references, cyan for external URLs).

Usage: add_link_borders.py <input.pdf> <output.pdf>
"""
import argparse

import pypdf
from pypdf.constants import AnnotationFlag
from pypdf.generic import ArrayObject, DictionaryObject, NameObject, NumberObject

RED = ArrayObject([NumberObject(1), NumberObject(0), NumberObject(0)])
CYAN = ArrayObject([NumberObject(0), NumberObject(1), NumberObject(1)])
BORDER = ArrayObject([NumberObject(0), NumberObject(0), NumberObject(1)])


def style_link_borders(writer: pypdf.PdfWriter) -> None:
    link_annots = (
        annot.get_object()
        for page in writer.pages
        for annot in (page.get("/Annots") or [])
    )
    for obj in link_annots:
        if obj.get("/Subtype") != "/Link":
            continue
        is_external = obj.get("/A") is not None and obj["/A"].get("/S") == "/URI"
        obj[NameObject("/Border")] = BORDER
        obj[NameObject("/BS")] = DictionaryObject({NameObject("/W"): NumberObject(1)})
        obj[NameObject("/C")] = CYAN if is_external else RED
        # Clear only the Print bit rather than deleting /F outright
        flags = int(obj.get("/F", 0))
        obj[NameObject("/F")] = NumberObject(flags & ~AnnotationFlag.PRINT)


def write_pdf(pdf_path: str, out_path: str) -> None:
    writer = pypdf.PdfWriter(clone_from=pdf_path)
    style_link_borders(writer)
    with open(out_path, "wb") as f:
        writer.write(f)


if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("pdf_path")
    ap.add_argument("out_path")
    args = ap.parse_args()
    write_pdf(args.pdf_path, args.out_path)
