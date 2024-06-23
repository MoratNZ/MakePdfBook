<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use \WikiPage;

class Chapter implements \JsonSerializable
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
    public function jsonSerialize(): array
    {
        return [
            "title" => $this->title->getText(),
            "sortKey" => $this->sortKey
        ];
    }
    private function fetchHtmlContent(): Chapter
    {
        global $wgUploadDirectory, $wgScriptPath, $wgUser, $wgServer;

        $this->htmlContent = $this->page->getParserOutput()->getText();

        # Replace http paths to images with a path to that image in the server's filesystem
        $scriptPath = $wgServer . $wgScriptPath;
        $urlPath = parse_url($scriptPath, PHP_URL_PATH);
        $imgpath = str_replace(
            '/',
            '\/',
            $urlPath . '/' . basename($wgUploadDirectory)  // the image's path
        );
        $this->htmlContent = preg_replace(
            "|(<img[^>]+?src=\"$imgpath)(/.+?>)|",
            "<img src=\"$wgUploadDirectory$2",
            $this->htmlContent
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
        file_put_contents($fileName, $this->htmlContent);
    }

}