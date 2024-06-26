<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\Extension\MakePdfBook\Chapter;
use MediaWiki\Title\Title;
use \OutOfBoundsException;

class Book implements \JsonSerializable
{
    private string $category;
    private ?string $cacheHash = null;
    public BookSet $bookSet;
    public Title $title;
    public ?Chapter $titlepage = null;
    public ?Title $contentsPage = null;
    private array $chapters = [];
    public function __construct(string $category)
    {
        $this->category = $category;
        $this->title = Title::newFromText(sprintf("Category:%s", $category));
    }
    public function jsonSerialize(): array
    {
        return [
            "title" => $this->title->getText(),
            "titlepage" => $this->titlepage,
            "chapters" => $this->getChapters(),
        ];
    }
    public function setTitlepage(int $pageId): Book
    {
        $this->titlepage = new Chapter($this, $pageId, $this->bookSet->titlepageSortKey);
        return $this;
    }
    public function setContentsPage(int $pageId): Book
    {
        $this->contentsPage = Title::newFromID($pageId);
        return $this;
    }
    public function addChapter(int $pageId, string $sortKey): Book
    {
        $title = Title::newFromID($pageId);
        $newChapter = new Chapter($this, $pageId, $sortKey);
        $newChapter->book = $this;

        $this->chapters[$title->getPrefixedText()] = $newChapter;
        return $this;
    }
    public function getChapter(string $title): Chapter
    {
        if (array_key_exists($title, $this->chapters)) {
            return $this->chapters[$title];
        } else {
            throw new OutOfBoundsException(sprintf("No chapter called '%s' in %s", $title, $this->category));
        }
    }
    public function getFullUrl(): string
    {
        global $wgServer, $wgScriptPath;
        return sprintf("%s%s/index.php/Category:%s", $wgServer, $wgScriptPath, $this->category);
    }
    public function getUrl(): string
    {
        if (empty($this->contentsPage)) {
            return sprintf("/index.php/Category:%s", $this->category);
        } else {
            return $this->contentsPage->getLocalURL();
        }
    }
    public function getPdfLink(): string
    {
        global $wgServer, $wgScriptPath;
        return sprintf("%s%s/index.php/Special:MakePdfBook/render/%s", $wgServer, $wgScriptPath, $this->category); #TODO make this nicer
    }
    // public function fetchTitlePage(): Book
    // {
    //     # TODO: This is currently bypassed, with BookSet always grabbing all
    //     # chapters and titlepages, as this is getting the wrong book's 
    //     # page under some circumstances, and I can't see why. 

    //     $query = $this->bookSet->dbr->newSelectQueryBuilder()
    //         ->select([
    //             'page_id',
    //             'cl_sortkey_prefix',
    //         ])
    //         ->from('page')
    //         ->join('categorylinks', null, 'page_id=cl_from')
    //         ->join('category', null, 'cl_to=cat_title')
    //         ->where(
    //             "cl_sortkey_prefix like '%titlepage%'",
    //             sprintf("cat_title=\"%s\"", $this->category)
    //         )
    //         ->caller('MakePdfBook');

    //     $result = $query->fetchRow();

    //     if ($result) {
    //         $pageId = $result->page_id;
    //         $sortKey = $result->cl_sortkey_prefix;

    //         $this->setTitlepage($pageId);
    //     }
    //     return $this;
    // }
    public function containsChapter($title): bool
    {
        return array_key_exists($title, $this->chapters);
    }
    // public function fetchChapters(): Book
    // {
    //     # TODO: This is currently bypassed, with BookSet always grabbing all
    //     # chapters and titlepages, as this is getting the wrong book's 
    //     # page under some circumstances, and I can't see why. 
    //     $query = $this->bookSet->dbr->newSelectQueryBuilder()
    //         ->select([
    //             'cat_title',
    //             'page_id',
    //             'cl_sortkey_prefix',
    //         ])
    //         ->from('page')
    //         ->join('categorylinks', null, 'page_id=cl_from')
    //         ->join('category', null, 'cl_to=cat_title')
    //         ->where(
    //             "cl_sortkey_prefix not like '%titlepage%'",
    //             sprintf("cat_title=\"%s\"", $this->category)
    //         )
    //         ->caller('MakePdfBook');

    //     $result = $query->fetchResultSet();

    //     foreach ($result as $row) {
    //         $pageId = $row->page_id;
    //         $sortKey = $row->cl_sortkey_prefix;

    //         $this->addChapter($pageId, $sortKey);
    //     }
    //     return $this;
    // }
    public function getCacheHash(): string
    {
        if (empty($this->cacheHash)) {
            $cacheString = "";
            $relevantDirectories = ['src', 'bin'];

            # Get the last modified time for relevant files
            foreach ($relevantDirectories as $directory) {
                $path = sprintf(
                    "%s/../%s",
                    dirname(__FILE__),
                    $directory
                );
                $directoryFiles = scandir($path);
                foreach ($directoryFiles as $fileName) {
                    $fullName = sprintf("%s/%s", $path, $fileName);
                    $cacheString .= sprintf(
                        "%s:%s\n",
                        $fullName,
                        filemtime($fullName)
                    );
                }
            }
            # Get revision id for all chapters
            if ($this->titlepage) {
                $cacheString .= sprintf(
                    "%s:%s\n",
                    $this->titlepage->title->getText(),
                    $this->titlepage->getRevId()
                );
            }
            foreach ($this->chapters as $chapter) {
                $cacheString .= sprintf(
                    "%s:%s\n",
                    $chapter->title->getText(),
                    $chapter->getRevId()
                );
            }
            $this->cacheHash = md5($cacheString);
        }
        return $this->cacheHash;
    }
    public function getChapters()
    {
        $clonedChapters = [...$this->chapters];
        usort($clonedChapters, function ($a, $b) {
            if (is_int($a) && is_int($b)) {
                return $a - $b;
            } else {
                return strcmp($a->sortKey, $b->sortKey);
            }
        });
        return $clonedChapters;
    }
    public function writeContent($directory)
    {
        if (!empty($this->titlepage)) {
            $this->titlepage->writeHtml(sprintf("%s/titlepage.html", $directory));
        }
        $chapterNumber = 0;
        foreach ($this->getChapters() as $chapter) {
            $chapterNumber++;
            $chapter->writeHtml(sprintf("%s/chapter-%s.html", $directory, $chapterNumber));
        }
    }
}