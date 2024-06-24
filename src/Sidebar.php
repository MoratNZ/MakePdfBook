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
            $bookSet->fetchChapters();

            $page_name = $skin->getRelevantTitle()->getText();

            $html .= "<hr><span class = 'makepdfbook-sidebar-title'>Books</span>\n";
            $html .= "<div class = 'makepdfbook-book-list'>\n";
            foreach ($bookSet->getBooks() as $book) {
                $html .= sprintf(
                    "<div class='makepdfbook-book-title' ><a href = '%s'>%s</a></div>",
                    $book->getUrl(),
                    $book->title->getText()
                );
                $html .= sprintf(
                    "<div class='makepdfbook-pdf-icon' ><a href = '%s' ><img src='%s' /></a></div>",
                    $book->getPdfLink(),
                    "https://upload.wikimedia.org/wikipedia/commons/6/6c/PDF_icon.svg"
                );
                $html .= "</div>\n";
                if ($book->containsChapter($page_name)) {
                    $html .= "<div class='makepdfbook-book-chapters'>";
                    foreach ($book->getChapters() as $chapter) {
                        $html .= sprintf(
                            "<div class='makepdfbook-chapter-title'><a href = '%s'>%s</a></div>",
                            $chapter->title->getLocalURL(),
                            $chapter->title->getText()
                        );
                    }
                    $html .= "</div>";
                }

                $html .= "</li>\n";
            }
            $html .= "</ul>\n";
        }
    }
}