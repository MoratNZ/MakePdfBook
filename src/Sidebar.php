<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\Extension\MakePdfBook\BookSet;

class Sidebar
{
    public static function onSidebarBeforeOutput($skin, &$sidebar)
    {
        $user = $skin->getUser();

        // throw new \Exception(var_export($sidebar, return: true));

        $sidebar = [];
        $sidebar['navigation'] = [
            [
                "text" => "Main page",
                "href" => "/"
            ]
        ];
        if ($skin->getUser()->isRegistered()) {
            $sidebar['navigation'][] = [
                "text" => "Special Pages",
                "href" => "/index.php/Special:SpecialPages"
            ];
            $sidebar['navigation'][] = [
                "text" => "MakePdfBook",
                "href" => "/index.php/Special:MakePdfBook"
            ];
        }
    }
    public static function onSkinAfterPortlet($skin, $portletName, &$html)
    {
        if ($portletName == 'navigation') {
            $bookSet = new BookSet();

            $html .= "<hr><span class = 'makepdfbook-sidebar-title'>Books</span>\n";
            $html .= "<div class = 'makepdfbook-book-list'>\n";

            #TODO: this handling of marking the active book does not bring me joy
            foreach ($bookSet->getBooks() as $book) {
                $activeBook = (
                    $book->title->getText() == $skin->getRelevantTitle()->getText()
                    || $book->containsChapter($skin->getRelevantTitle()->getPrefixedText())
                );
                $html .= sprintf(
                    "<div class='makepdfbook-book-content%s'>",
                    $activeBook ? " makepdfbook-active-book" : ""
                );
                $html .= sprintf(
                    "<div class='makepdfbook-book-title' ><a href = '%s'>%s</a></div>\n",
                    $book->getUrl(),
                    $book->title->getText()
                );
                $html .= sprintf(
                    "<div class='makepdfbook-pdf-icon' ><a href = '%s' ><img src='%s' %s/></a></div>\n",
                    $book->getPdfLink(),
                    "https://upload.wikimedia.org/wikipedia/commons/6/6c/PDF_icon.svg",
                    "width='18' height='18'" #This is filithy - need to find a better way of avoiding FOUT - probably including and resizing the svg
                );
                if ($activeBook) {
                    $html .= self::generateChapterHtml($book);
                }
                $html .= "</div>";
            }
            $html .= "</div>";

        }
    }
    private static function generateChapterHtml($book)
    {
        $html = "<div class='makepdfbook-book-chapters'>\n";
        foreach ($book->getChapters() as $chapter) {
            $html .= sprintf(
                "<div class='makepdfbook-chapter-title'><a href = '%s'>%s</a></div>\n",
                $chapter->title->getLocalURL(),
                $chapter->title->getText()
            );
        }
        $html .= "</div>";

        return $html;
    }
}