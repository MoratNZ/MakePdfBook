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
        $namespace = $skin->getRelevantTitle()->getNsText();

    //     $sidebar = [];
        $sidebar['navigation'] = [
            [
                "text" => "Main page",
                "href" => "/"
            ]
        ];
        $sidebar['TOOLBOX'] = [];
        $sidebar['LANGUAGES'] = [];

        if ($skin->getUser()->isRegistered()) {
            $sidebar['TOOLBOX'][] = [
                "text" => "Special Pages",
                "href" => "/index.php/Special:SpecialPages"
            ];
            $sidebar['TOOLBOX'][] = [
                "text" => "Book assets",
                "href" => "/index.php/Special:MakePdfBook"
            ];
            $sidebar['TOOLBOX'][] = [
                "text" => "Namespace resources",
                "href" => "/index.php/Special:NamespaceResources"
            ];
            $sidebar['TOOLBOX'][] = [
                "text" => "File list",
                "href" => "/index.php/Special:ListFiles"
            ];
            $sidebar['TOOLBOX'][] = [
                "text" => "Upload file",
                "href" => "/index.php/Special:Upload"
            ];
        }
    }
    public static function onSkinAfterPortlet($skin, $portletName, &$html)
    {
        if ($portletName == 'navigation') {
            $pageRelevantTitle = $skin->getRelevantTitle();
            $pageName = $pageRelevantTitle->getPrefixedText();
            $nameParts = explode(':', $pageName);
            $namespace = $pageRelevantTitle->getNsText();

            $sidebarPageName = "MediaWiki:DefaultSidebar";

            if($namespace){
                $namespaceSidebarName = "$namespace:Sidebar";
                $namespaceSidebarTitle = Title::newFromText($namespaceSidebarName);
                if ($namespaceSidebarTitle->isKnown()) {
                    $sidebarPageName = $namespaceSidebarName;
                }
            }

            $sidebarTitle = Title::newFromText($sidebarPageName);
            $sidebarPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle($sidebarTitle);
            $sidebarText = $sidebarPage->getContent()->getText();

            $parser = MediaWikiServices::getInstance()->getParserFactory()->create();
            if ($skin->getUser()->isRegistered()) {
                $parserOptions = \ParserOptions::newFromUser($skin->getUser());
            } else {
                $parserOptions = \ParserOptions::newFromAnon();
            }
            $sidebarOutput = $parser->parse($sidebarText, $sidebarTitle, $parserOptions);
            $html .= $sidebarOutput->getText(options: [
                'allowTOC' => false,
                'enableSectionEditLinks' => false,
                'unwrap' => false,
                'deduplicateStyles' => true,
            ]);  

            $html .= self::getLogoAndBannerSetJs($skin);
            if (!$skin->getUser()->isRegistered()) {
                $html .= self::getHideRightNavJs($skin);
            }

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
            try{
                $fileUrl = $file->getUrl();
            } catch (Exception $e) {
                $fileUrl = "";
            }
        } else {
            $fileUrl = "";
        }

        return $fileUrl;
    }
    private static function getPageTitleParts($pageTitle)
    {
        $parts = explode('--', $pageTitle);
        if (count($parts) > 1) {
            $kingdom = array_shift($parts);
        } else {
            $kingdom = null;
        }
        $subparts = explode(':', $parts[0]);
        $handbook = $subparts[0] == "Armored Combat" ? "Armored Combat - Rattan" : $subparts[0];
        $chapter = $subparts[1] ?? null;

        return [$kingdom, $handbook, $chapter];
    }
    private static function pageBelongsToBook($pageName, $book)
    {
        if ($book->title && $book->title->getText() == $pageName) {
            return true;
        } else if ($book->contentsPage && $book->contentsPage->getPrefixedText() == $pageName) {
            return true;
        } else if ($book->containsChapter($pageName)) {
            return true;
        }
        return false;
    }
    private static function generateBookHtml($book, $activeChapter)
    {
        $html = "<div class='makepdfbook-book'>\n";
        $html .= sprintf(
            "<div class='makepdfbook-book-title'><a href = '%s'>%s</a></div>\n",
            $book->getUrl(),
            $book->title->getText()
        );
        if($activeChapter){
            $html .= self::generateChapterHtml($book, $activeChapter);
        }
        $html .= "</div>";
        return $html;
    }
    private static function generateChapterHtml($book, $activeChapter)
    {
        $sectionList = [];

        $html = "<div class='makepdfbook-book-chapters'>\n";
        foreach ($book->getChapters() as $chapter) {
            $chapterTitle = $chapter->title->getText();

            if (str_contains($chapterTitle, " - " )) {
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