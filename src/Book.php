<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;
use Chapter;

class Book
{
    public function __construct(string $category)
    {
        $this->category = $category;
        $this->chapters = [];
        $this->titlepage = null;
    }
    public function setTitlepage(Chapter $chapter)
    {
        $this->titlepage = $chapter;
        return $this;
    }
    public function addChapter($title, $sortKey)
    {
        $this->chapters[$title] = new Chapter($this, $title, $sortKey);
        return $this;
    }
    public function render()
    {
    }
}