<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\Extension\MakePdfBook\Chapter;
use MediaWiki\Title\Title;
use \OutOfBoundsException;

class Book implements \JsonSerializable
{
    private string $category;
    public BookSet $bookSet;
    public Title $title;
    public ?Chapter $titlepage = null;
    protected array $chapters = [];
    const TITLEPAGE_SORTKEY = 'titlepage';
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
            "chapters" => $this->chapters,
        ];
    }
    public function setTitlepage(int $pageId): Book
    {
        $this->titlepage = new Chapter($this, $pageId, self::TITLEPAGE_SORTKEY);
        return $this;
    }
    public function addChapter(int $pageId, string $sortKey): Book
    {
        $title = Title::newFromID($pageId);
        $newChapter = new Chapter($this, $pageId, $sortKey);
        $newChapter->book = $this;

        $this->chapters[$title->getText()] = $newChapter;
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
    public function getPdfLink(): string
    {
        global $wgServer, $wgScriptPath;
        return sprintf("%s%s/index.php/Special:MakePdfBook?category=%s", $wgServer, $wgScriptPath, $this->category);
    }
    public function render()
    {
        $bob = array_keys($this->chapters)[0];
        return $this->chapters[$bob]->getHtmlContent();
    }
    public function fetchTitlePage(): Book
    {
        $query = $this->bookSet->dbr->newSelectQueryBuilder()
            ->select([
                'page_id',
                'cl_sortkey_prefix',
            ])
            ->from('page')
            ->join('categorylinks', null, 'page_id=cl_from')
            ->join('category', null, 'cl_to=cat_title')
            ->where(
                "cl_sortkey_prefix like '%titlepage%'",
                sprintf("cat_title=\"%s\"", $this->category)
            )
            ->caller('MakePdfBook');

        $result = $query->fetchRow();

        if ($result) {
            $pageId = $result->page_id;
            $sortKey = $result->cl_sortkey_prefix;

            $this->setTitlepage($pageId);
        }
        return $this;
    }
    public function fetchChapters(): Book
    {
        $query = $this->bookSet->dbr->newSelectQueryBuilder()
            ->select([
                'cat_title',
                'page_id',
                'cl_sortkey_prefix',
            ])
            ->from('page')
            ->join('categorylinks', null, 'page_id=cl_from')
            ->join('category', null, 'cl_to=cat_title')
            ->where(
                "cl_sortkey_prefix not like '%titlepage%'",
                sprintf("cat_title=\"%s\"", $this->category)
            )
            ->caller('MakePdfBook');

        $result = $query->fetchResultSet();

        foreach ($result as $row) {
            $pageId = $row->page_id;
            $sortKey = $row->cl_sortkey_prefix;

            $this->addChapter($pageId, $sortKey);
        }
        return $this;
    }
}