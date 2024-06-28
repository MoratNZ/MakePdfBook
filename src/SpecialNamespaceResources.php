<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use \OutOfBoundsException;

class SpecialNamespaceResources extends SpecialPage
{
    const NAMESPACE_INDEX_MIN = 1000; # lowest namespace index we care about
    const NAMESPACE_INDEX_MAX = 1100; # highest namespace index we care about
    private $config;
    private $parser;
    public function __construct()
    {
        parent::__construct('NameSpaceResources');
        $this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig('MakePdfBook');
        $this->parser = MediaWikiServices::getInstance()->getParser();
    }
    public function execute($subpage) # $subpage parameter included for signature compatibility only
    {
        $textString = "=Namespace Resources=\n";
        $textString .= $this->drawNamespaceTable($this->getNamespaces());
        $textString .= "==Notes==\n";
        $textString .= "Logo and Banner pages are used to provide dynamic logos and banners for namespaces.\n\n";
        $textString .= "The first image in the page will be used for this purpose, so the page can consist of just a single image tag.\n";

        $this->getOutput()->addWikiTextAsInterface($textString);
    }
    public function getGroupName()
    {
        return 'other';
    }
    public function getDescription()
    {
        return wfMessage("Namespace resources");
    }
    private function getNamespaces(): array
    {
        global $wgExtraNamespaces, $wgContentNamespaces;
        $namespaces = [];
        foreach ($wgContentNamespaces as $namespaceIndex) {
            if ($namespaceIndex >= self::NAMESPACE_INDEX_MIN && $namespaceIndex <= self::NAMESPACE_INDEX_MAX) {
                if (key_exists($namespaceIndex, $wgExtraNamespaces)) {
                    $namespace = $wgExtraNamespaces[$namespaceIndex];
                    $namespaces[$namespace] = $namespaceIndex;
                }
            }
        }
        asort($namespaces);
        return $namespaces;
    }
    public function drawNamespaceTable(array $namespaces): string
    {
        $textString = "{| class=\"wikitable\"\n|-\n!Namespace\n!Logo page\n!Banner page\n";
        $textString .= "|-\n!Default\n|[Mediawiki:Logo|logo]]\n|[[Mediawiki:Banner|banner]]\n";

        foreach ($namespaces as $namespace => $namespaceIndex) {
            $textString .= sprintf(
                "|-\n![%s %s]\n|[[%s:Logo|logo]]\n|[[%s:Banner|banner]]\n",
                sprintf("{{fullurl:Special:PrefixIndex|namespace=%s}}", $namespaceIndex),
                $namespace,
                $namespace,
                $namespace,
                $namespace
            );
        }
        $textString .= "|}\n";
        return $textString;
    }
}