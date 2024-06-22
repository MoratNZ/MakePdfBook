<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\MakePdfBook\Chapter;


class Book
{
    private string $category;
    public \Title $title;
    public ?Chapter $titlepage = null;
    protected array $chapters = [];
    public static string $TITLEPAGE_SORTKEY = 'titlepage';
    public function __construct(string $category)
    {
        $this->category = $category;
        $this->title = \Title::newFromText(sprintf("Category:%s", $category));
    }
    public function setTitlepage(int $pageId): Book
    {
        $this->titlepage = new Chapter($this, $pageId, Book::$TITLEPAGE_SORTKEY);
        return $this;
    }
    public function addChapter(int $pageId, string $sortKey): Book
    {
        $title = \Title::newFromID($pageId);

        $this->chapters[$title->get_text] = new Chapter($this, $title, $sortKey);
        return $this;
    }
    public function getChapter(string $title): Chapter
    {
        if (array_key_exists($title, $this->chapters)) {
            return $this->chapters[$title];
        } else {
            throw new \InvalidArgumentException(sprintf("No chapter called '%s' in %s", $title, $this->category));
        }
    }
    public function getFullUrl(): string
    {
        global $wgServer, $wgScriptPath;
        return sprintf("%s%s/index.php/Category:%s", $wgServer, $wgScriptPath, $this->category);
    }
    public function getPdfLink(): string
    {
        global $wgServer, $wgScriptPath;
        return sprintf("%s%s/index.php/Special:MakePdfBook?category=%s", $wgServer, $wgScriptPath, $this->category);
    }
    public function render()
    {
    }
}