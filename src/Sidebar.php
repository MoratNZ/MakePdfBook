<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\Extension\MakePdfBook\BookSet;
use MediaWiki\Title\Title;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;

class Sidebar
{
    public static function onSidebarBeforeOutput($skin, &$sidebar)
    {
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
            $sidebar['navigation'][] = [
                "text" => "File list",
                "href" => "/index.php/Special:ListFiles"
            ];
            $sidebar['navigation'][] = [
                "text" => "Upload file",
                "href" => "/index.php/Special:Upload"
            ];


        }
    }
    public static function onSkinAfterPortlet($skin, $portletName, &$html)
    {
        if ($portletName == 'navigation') {
            $bookSet = new BookSet();

            $pageRelevantTitle = $skin->getRelevantTitle();

            $html .= "<hr><span class = 'makepdfbook-sidebar-title'>Books</span>\n";
            $html .= "<div class = 'makepdfbook-book-list'>\n";

            foreach ($bookSet->getBooks() as $book) {
                if ($book->title && $book->title->getText() == $pageRelevantTitle->getText()) {
                    $activeBook = true;
                } else if ($book->contentsPage && $book->contentsPage->getPrefixedText() == $pageRelevantTitle->getPrefixedText()) {
                    $activeBook = true;
                } else if ($book->containsChapter($pageRelevantTitle->getPrefixedText())) {
                    $activeBook = true;
                } else {
                    $activeBook = false;
                }

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
            $html .= sprintf(
                "<script>
                document.addEventListener(\"DOMContentLoaded\",function(){
                    document.getElementsByClassName(\"mw-wiki-logo\")[0].style.backgroundImage ='url(\"%s\")';
                });
                </script>",
                self::getNsLogoUrl($pageRelevantTitle->getNsText(), 'Logo', $skin->getUser())
            );
        }
    }
    private static function getNsNamedPageUrl(string $namespace, $pageName, $user): ?string
    {
        $nsLogoPageTitle = Title::newFromText(
            sprintf(
                "%s:%s",
                $namespace,
                "Logo" #TODO change this to referencing configured magic word
            )
        );
        if ($nsLogoPageTitle->isKnown()) {
            return self::getFirstImageUrlFromTitle($nsLogoPageTitle, $user);
        }
        $defaultLogoPageTitle = $nsLogoPageTitle = Title::newFromText(
            sprintf(
                "Mediawiki:%s",
                "Logo" #TODO change this to referencing configured magic word
            )
        );
        if ($defaultLogoPageTitle->isKnown()) {
            return self::getFirstImageUrlFromTitle($nsLogoPageTitle, $user);
        }
        return null;
    }
    private static function getFirstImageUrlFromTitle(Title $title, $user): string
    {
        $logoPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle($title);
        $logoPageText = $logoPage->getContent()->getText();

        $parser = MediaWikiServices::getInstance()->getParserFactory()->create();

        if ($user->isRegistered()) {
            $parserOptions = \ParserOptions::newFromUser($user);
        } else {
            $parserOptions = \ParserOptions::newFromAnon();
        }
        $output = $parser->parse($logoPageText, $title, $parserOptions);
        $firstImage = array_keys($output->getImages())[0];

        $fileTitle = Title::newFromText(sprintf("File:%s", $firstImage));
        $file = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileTitle);
        $fileUrl = $file->getUrl();

        return $fileUrl;
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