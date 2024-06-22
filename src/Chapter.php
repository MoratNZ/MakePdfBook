<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;

class Chapter
{
    public Book $book;
    private int $pageId;
    public \Title $title;
    public string $sortKey;
    public ?int $number = null;
    private ?string $htmlContent = null;
    public function __construct(Book $book, int $pageId, string $sortKey)
    {
        $this->book = $book;
        $this->page_id = $pageId;
        $this->title = \Title::newFromID($pageId);
        $this->sortKey = $sortKey;
    }
    public function getLink(): string
    {
        return $this->title->getLocalUrl();
    }
}