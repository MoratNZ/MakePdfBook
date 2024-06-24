<?php
namespace MediaWiki\Extension\MakePdfBook;

use \OutOfBoundsException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\MakePdfBook\Book;
use \Wikimedia\Rdbms\DBConnRef;
use MediaWiki\Title\Title;

class BookSet implements \JsonSerializable
{
    private array $books = [];
    public DBConnRef $dbr;
    public function __construct()
    {
        $instance = MediaWikiServices::getInstance();
        $lb = $instance->getDBLoadBalancer();

        $this->dbr = $lb->getConnection(DB_REPLICA);
        $this->fetchBooks()->fetchChapters()->fetchTitlePages();
    }
    public function jsonSerialize(): array
    {
        return [
            "books" => $this->getBooks()
        ];
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
        $newBook->bookSet = $this;
        $this->books[$category] = $newBook;
        return $newBook;
    }
    public function getBook(string $category): Book
    {
        $title = Title::newFromText($category)->getDBkey();
        $books = $this->getBooks();

        foreach ($books as $book) {
            if ($book->title->getDBkey() == $title) {
                return $book;
            }
        }
        throw new OutOfBoundsException(sprintf('There is no such book as "%s"', $category));

        // if (array_key_exists($title, $books)) {
        //     return $books[$title];
        // } else {
        //     throw new OutOfBoundsException(sprintf('There is no such book as "%s"', $category));
        // }
    }
    public function getBooks(bool $sorted = false): array
    {
        $clonedBooks = [...$this->books];
        if ($sorted) {
            arsort($clonedBooks);
        }
        return $clonedBooks;
    }
    public function fetchTitlePages(): BookSet
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
    public function fetchChapters(): BookSet
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