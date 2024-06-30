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
    public string $titlepageSortKey;
    public string $contentsSortKey;
    public string $copyrightSortKey;
    private string $bookTag = 'book';

    public DBConnRef $dbr;
    public function __construct()
    {
        global $makepdfTitlepageSortKey, $makepdfContentsSortKey, $makepdfCopyrightSortKey;
        $this->titlepageSortKey = $makepdfTitlepageSortKey ? $makepdfTitlepageSortKey : 'titlepage';
        $this->contentsSortKey = $makepdfContentsSortKey ? $makepdfContentsSortKey : 'contents';
        $this->copyrightSortKey = $makepdfCopyrightSortKey ? $makepdfCopyrightSortKey : 'copyright';

        $instance = MediaWikiServices::getInstance();
        $lb = $instance->getDBLoadBalancer();

        $this->dbr = $lb->getConnection(DB_REPLICA);
        $this->fetchBooks()->fetchContent();
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
            # This if check shouldn't be necessary, given the WHERE/LIKE statement in the query above
            # Unfortunately at the moment some combination of MediaWiki's RDBMS layer and the varbinary
            # columns in the database means that the WHERE/LIKE statement isnt' working correctly
            if (str_contains($category, $this->bookTag)) {
                $this->addBook($category);
            }
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
            asort($clonedBooks);
        }
        return $clonedBooks;
    }
    public function fetchContent(): BookSet
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
            ->caller('MakePdfBook');

        $result = $query->fetchResultSet();

        foreach ($result as $row) {
            $category = $row->cat_title;
            $pageId = $row->page_id;
            $sortKey = $row->cl_sortkey_prefix;

            try {
                $book = $this->getBook($category);
            } catch (OutOfBoundsException $e) {
                $book = $this->addBook($category);
            }
            switch ($sortKey) {
                case $this->titlepageSortKey:
                    $book->setTitlepage($pageId);
                    break;
                case $this->contentsSortKey:
                    $book->setContentsPage($pageId);
                    break;
                case $this->copyrightSortKey:
                    # we don't care about this, as this will be transcluded into the title page
                    break;
                default:
                    $book->addChapter($pageId, $sortKey);
            }
        }
        return $this;
    }
    // public function fetchTitlePages(): BookSet
    // {
    //     $query = $this->dbr->newSelectQueryBuilder()
    //         ->select([
    //             'cat_title',
    //             'page_id',
    //             'cl_sortkey_prefix',
    //         ])
    //         ->from('page')
    //         ->join('categorylinks', null, 'page_id=cl_from')
    //         ->join('category', null, 'cl_to=cat_title')
    //         ->where("cl_sortkey_prefix like '%titlepage%'")
    //         ->caller('MakePdfBook');

    //     $result = $query->fetchResultSet();

    //     foreach ($result as $row) {
    //         $category = $row->cat_title;
    //         $pageId = $row->page_id;

    //         try {
    //             $this->getBook($category)->setTitlepage($pageId);
    //         } catch (OutOfBoundsException $e) {
    //             $this->addBook($category)->setTitlepage($pageId);
    //         }
    //     }
    //     return $this;
    // }
    // public function fetchChapters(): BookSet
    // {
    //     $query = $this->dbr->newSelectQueryBuilder()
    //         ->select([
    //             'cat_title',
    //             'page_id',
    //             'cl_sortkey_prefix',
    //         ])
    //         ->from('page')
    //         ->join('categorylinks', null, 'page_id=cl_from')
    //         ->join('category', null, 'cl_to=cat_title')
    //         ->where("cl_sortkey_prefix not like '%titlepage%'")
    //         ->caller('MakePdfBook');

    //     $result = $query->fetchResultSet();

    //     foreach ($result as $row) {
    //         $category = $row->cat_title;
    //         $pageId = $row->page_id;
    //         $sortKey = $row->cl_sortkey_prefix;

    //         try {
    //             $this->getBook($category)->addChapter($pageId, $sortKey);
    //         } catch (OutOfBoundsException $e) {
    //             $this->addBook($category)->addChapter($pageId, $sortKey);
    //         }
    //     }
    //     return $this;
    // }
}