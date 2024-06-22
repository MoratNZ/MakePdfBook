<?php
namespace MediaWiki\Extension\MakePdfBook;

use \OutOfBoundsException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\MakePdfBook\Book;
use \Wikimedia\Rdbms\DBConnRef;

class Books
{
    private array $books = [];
    public DBConnRef $dbr;
    public function __construct()
    {
        $instance = MediaWikiServices::getInstance();
        $lb = $instance->getDBLoadBalancer();

        $this->dbr = $lb->getConnection(DB_REPLICA);
        $this->fetchBooks();
    }
    private function fetchBooks()
    {
        # the book categories
        $query = $this->dbr->newSelectQueryBuilder()
            ->select(['cat_title'])
            ->from('category')
            ->where("cat_title like '%book%'")
            ->caller('MakePdfBook');
        $result = $query->fetchResultSet();

        foreach ($result as $row) {
            $category = $row->cat_title;
            $this->addBook($category);
        }
        return $this;
    }
    private function addBook(string $category): Book
    {
        $newBook = new Book($category);
        $this->books[$category] = $newBook;
        return $newBook;
    }
    public function getBook(string $category): Book
    {
        if (array_key_exists($category, $this->books)) {
            return $this->books[$category];
        } else {
            throw new OutOfBoundsException(sprintf('There is no such book as "%s"', $category));
        }
    }
    public function getBooks(bool $sorted = false): array
    {
        $clonedBook = [...$this->books];
        if ($sorted) {
            arsort($clonedBook);
        }
        return $clonedBook;
    }
    public function getTitlePages(): Books
    {
        $query = $this->dbr->newSelectQueryBuilder()
            ->select([
                'cat_title',
                'page_id',
                'cl_sortkey_prefix',
            ])
            ->from('page')
            ->join('categorylinks', null, 'page_id=cl_from')
            ->join('category', null, 'cl_to=cat_title')
            ->where("cl_sortkey_prefix like '%titlepage%'")
            ->caller('MakePdfBook');

        $result = $query->fetchResultSet();

        foreach ($result as $row) {
            $category = $row->cat_title;
            $pageId = $row->page_id;

            try {
                $this->getBook($category)->setTitlepage($pageId);
            } catch (OutOfBoundsException $e) {
                $this->addBook($category)->setTitlepage($pageId);
            }
        }
        return $this;
    }
    public function getChapters(): Books
    {
        $query = $this->dbr->newSelectQueryBuilder()
            ->select([
                'cat_title',
                'page_id',
                'cl_sortkey_prefix',
            ])
            ->from('page')
            ->join('categorylinks', null, 'page_id=cl_from')
            ->join('category', null, 'cl_to=cat_title')
            ->where("cl_sortkey_prefix not like '%titlepage%'")
            ->caller('MakePdfBook');

        $result = $query->fetchResultSet();

        foreach ($result as $row) {
            $category = $row->cat_title;
            $pageId = $row->page_id;
            $sortKey = $row->cl_sortkey_prefix;

            try {
                $this->getBook($category)->addChapter($pageId, $sortKey);
            } catch (OutOfBoundsException $e) {
                $this->addBook($category)->addChapter($pageId, $sortKey);
            }
        }
        return $this;
    }
}