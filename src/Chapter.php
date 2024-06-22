<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;

class Chapter
{
    public function __construct(Book $book, string $title, string $sortKey)
    {
        $this->book = $book;
        $this->title = $title;
        $this->sortKey = $sortKey;
        $this->number = null;
        $this->htmlContent = null;
    }
}