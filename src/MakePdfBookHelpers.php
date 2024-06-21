<?php
use MediaWiki\MediaWikiServices;

class MakePdfBookHelpers
{
    public static function getBooks($category = false)
    {
        $articles = array();

        $instance = MediaWikiServices::getInstance();
        $lb = $instance->getDBLoadBalancer();
        $dbr = $lb->getConnection(DB_REPLICA);

        $whereClause = [
            "cat_title like '%book%'",
            "cl_sortkey_prefix not like '%titlepage%'",
        ];
        if ($category) {
            $whereClause[] = "cat_title =\"$category\""; # TODO: replace with an expr() call once we get MW 1.42
        }

        # Get the non-titlepage pages
        $query = $dbr->newSelectQueryBuilder()
            ->select([
                'cat_title',
                'page_id',
                'cl_sortkey_prefix',
            ])
            ->from('page')
            ->join('categorylinks', null, 'page_id=cl_from')
            ->join('category', null, 'cl_to=cat_title')
            ->where($whereClause)
            ->caller('MakePdfBook');
        $result = $query->fetchResultSet();


        foreach ($result as $row) {
            $category = $row->cat_title;
            $page_id = $row->page_id;
            $sortKey = $row->cl_sortkey_prefix;

            $page = Title::newFromID($page_id);

            if (!array_key_exists($category, $articles)) {
                $articles[$category] = [
                    'title' => $category,
                    'chapters' => []
                ];
            }

            $articles[$category]['chapters'][$sortKey] = [
                'title' => $page->getText(),
                'sortKey' => $sortKey,
                'url' => $page->getFullUrl(),
            ];
        }
        # Get the titlepage pages
#
# This is being done like this so that we can have fuzzy titlepage
# labelling in the wiki, but precise identification of titlepages here

        $whereClause[1] = "cl_sortkey_prefix like '%titlepage%'";
        $query = $dbr->newSelectQueryBuilder()
            ->select([
                'cat_title',
                'page_id',
                'cl_sortkey_prefix',
            ])
            ->from('page')
            ->join('categorylinks', null, 'page_id=cl_from')
            ->join('category', null, 'cl_to=cat_title')
            ->where($whereClause)
            ->caller('MakePdfBook');

        $result = $query->fetchResultSet();

        foreach ($result as $row) {
            $category = $row->cat_title;
            $page_id = $row->page_id;
            $sortKey = $row->cl_sortkey_prefix;

            $page = Title::newFromID($page_id);

            $titlePage = [
                'title' => $page->getText(),
                'url' => $page->getFullUrl(),
            ];
            if (!array_key_exists($category, $articles)) {
                $articles[$category] = [
                    'title' => $category,
                    'chapters' => [],
                    'titlepage' => $titlePage
                ];
            } else {
                $articles[$category]['titlepage'] = $titlePage;
            }
        }

        return $articles;
    }
}