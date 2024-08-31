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
                "text" => "Book assets",
                "href" => "/index.php/Special:MakePdfBook"
            ];
            $sidebar['navigation'][] = [
                "text" => "Namespace resources",
                "href" => "/index.php/Special:NamespaceResources"
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

            $activeChapter = $pageRelevantTitle->getPrefixedText();

            foreach ($bookSet->getBooks() as $book) {
                if ($book->title && $book->title->getText() == $pageRelevantTitle->getText()) {
                    $activeBook = true;
                } else if ($book->contentsPage && $book->contentsPage->getPrefixedText() == $activeChapter) {
                    $activeBook = true;
                } else if ($book->containsChapter($activeChapter)) {
                    $activeBook = true;
                } else {
                    $activeBook = false;
                }

                $html .= sprintf(
                    "<div class='makepdfbook-book-content%s'>",
                    $activeBook ? " makepdfbook-active-book" : ""
                );
                $html .= sprintf(
                    "<div class='makepdfbook-book-title'><a href = '%s'>%s</a></div>\n",
                    $book->getUrl(),
                    $book->title->getText()
                );
                $html .= sprintf(
                    "<div class='makepdfbook-pdf-icon' ><a href = '%s' ><img src='%s' %s/></a></div>\n",
                    $book->getPdfLink(),
                    "https://upload.wikimedia.org/wikipedia/commons/6/6c/PDF_icon.svg",
                    "width='18' height='18'" #This is filthy - need to find a better way of avoiding FOUT - probably including and resizing the svg
                );
                if ($activeBook) {
                    $html .= self::generateChapterHtml($book, $activeChapter);
                }
                $html .= "</div>";
            }
            $html .= self::getLogoAndBannerSetJs($skin);
            if (!$skin->getUser()->isRegistered()) {
                $html .= self::getHideRightNavJs($skin);
            }

            $html .= "</div>";

        }
    }
    private static function getLogoAndBannerSetJs($skin)
    {
        $pageRelevantTitle = $skin->getRelevantTitle();
        return sprintf(
            "<script>
    document.addEventListener(\"DOMContentLoaded\",function(){
        document.getElementsByClassName(\"mw-wiki-logo\")[0].style.backgroundImage ='url(\"%s\")';
        
        let mwHeadBaseStyle = document.getElementById(\"mw-head-base\").style;
        mwHeadBaseStyle.backgroundImage = 'url(\"%s\")';
        mwHeadBaseStyle.backgroundRepeat = \"no-repeat\";
    });</script>",
            #TODO change these to referencing configured magic word
            self::getNsNamedPageUrl($pageRelevantTitle->getNsText(), 'Logo', $skin->getUser()),
            self::getNsNamedPageUrl($pageRelevantTitle->getNsText(), 'Banner', $skin->getUser())
        );
    }
    private static function getHideRightNavJs()
    {
        return "<script>
    document.addEventListener(\"DOMContentLoaded\",function(){
        ['p-views', 'p-cactions'].forEach(
            (tag)=> document.getElementById(tag).style.display='none'
        );
    });</script>\n";
    }
    private static function getNsNamedPageUrl(string $namespace, $imageType, $user): ?string
    {
        $nsLogoPageTitle = Title::newFromText(
            sprintf(
                "%s:%s",
                $namespace,
                $imageType
            )
        );
        if ($nsLogoPageTitle->isKnown()) {
            return self::getFirstImageUrlFromTitle($nsLogoPageTitle, $user);
        }
        $defaultLogoPageTitle = $nsLogoPageTitle = Title::newFromText(
            sprintf(
                "Mediawiki:%s",
                $imageType
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

        $pageImages = array_keys($output->getImages());

        if (key_exists(0, $pageImages)) {
            $firstImage = $pageImages[0];
            $fileTitle = Title::newFromText(sprintf("File:%s", $firstImage));
            $file = MediaWikiServices::getInstance()->getRepoGroup()->findFile($fileTitle);
            $fileUrl = $file->getUrl();
        } else {
            $fileUrl = "";
        }

        return $fileUrl;
    }
    private static function generateChapterHtml($book, $activeChapter)
    {
        $sectionList = [];

        $html = "<div class='makepdfbook-book-chapters'>\n";
        foreach ($book->getChapters() as $chapter) {
            $chapterTitle = $chapter->title->getText();

            if (str_contains($chapterTitle, "-")) {
                [$sectionName, $chapterTitle] = explode("-", $chapterTitle);
                if (!array_key_exists($sectionName, $sectionList)) {
                    $sectionList[$sectionName] = [];
                }
                $sectionList[$sectionName][$chapterTitle] = $chapter;
            } else {
                $sectionList[$chapterTitle] = $chapter;
            }
        }

        foreach ($sectionList as $sectionTitle => $chapters) {
            if (is_array($chapters)) {
                $childHtml = "";
                $isActive = false;

                foreach ($chapters as $chapterTitle => $chapter) {
                    if ($chapter->title->getPrefixedText() == $activeChapter) {
                        $isActive = true;
                    }
                    $childHtml .= sprintf(
                        "<div class='makepdfbook-chapter-in-section'><a href = '%s'>%s</a></div>\n",
                        $chapter->title->getLocalURL(),
                        $chapterTitle
                    );
                }
                $html .= sprintf(
                    "<div class='makepdfbook-chapter-title makepdfbook-has-children%s'><a>%s</a>\n%s</div>",
                    $isActive ? " makepdfbook-active-section" : "",
                    $sectionTitle,
                    $childHtml
                );

            } else {
                $html .= sprintf(
                    "<div class='makepdfbook-chapter-title'><a href = '%s'>%s</a></div>\n",
                    $chapters->title->getLocalURL(),
                    $chapters->title->getText()
                );
            }
        }
        $html .= "</div>";
        $html .= "<script>
        document.addEventListener(\"DOMContentLoaded\",function(){
            console.log('bong');
            for (const parent of document.querySelectorAll('.makepdfbook-has-children')) {
                parent.addEventListener('click', function(clickEvent) {
                    if (clickEvent.target === parent.querySelector('a') ){
                        if(parent.classList.contains('makepdfbook-active-section')){
                            parent.classList.remove('makepdfbook-active-section');
                        } else {
                            parent.classList.add('makepdfbook-active-section');
                        }
                    } 
                })
            }
        });</script>";
        return $html;
    }
}