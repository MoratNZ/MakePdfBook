<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use \WikiPage;

class Chapter
{
    public Book $book;
    private int $pageId;
    public Title $title;
    private WikiPage $page;
    public string $sortKey;
    public ?int $number = null;
    private ?string $htmlContent = null;
    public function __construct(Book $book, int $pageId, string $sortKey)
    {
        $this->book = $book;
        $this->page_id = $pageId;
        $this->title = \Title::newFromID($pageId);
        $this->page = new Wikipage($this->title);
        $this->sortKey = $sortKey;
    }
    private function fetchHtmlContent(): Chapter
    {
        if ($this->isTitlepage()) {
            $formatString = "%s";
        } else {
            $formatString = sprintf(
                "<h1>%s</h1>\n%%s",
                $this->title->getText()
            );
        }
        $this->htmlContent = sprintf(
            $formatString,
            $this->page->getParserOutput()->getText()
        );
        return $this;
    }
    private function isTitlepage(): bool
    {
        return $this->sortKey === Book::TITLEPAGE_SORTKEY;
    }
    public function getHtmlContent(): string
    {
        if (empty($this->htmlContent)) {
            return $this->fetchHtmlContent()->htmlContent;
        } else {
            return $this->htmlContent;
        }
    }
    public function saveAs($fileName): void
    {
    }

}