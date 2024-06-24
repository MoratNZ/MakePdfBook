<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\Extension\MakePdfBook\Sidebar;

class MakePdfBookHooks
{
    public static function onBeforePageDisplay(&$out)
    {
        $out->addModules('ext.makePdfBook');
        return true;
    }
    public static function onSidebarBeforeOutput($skin, &$sidebar)
    {
        Sidebar::onSidebarBeforeOutput($skin, $sidebar);
    }
    public static function onSkinAfterPortlet($skin, $portletName, &$html)
    {
        Sidebar::onSkinAfterPortlet($skin, $portletName, $html);
    }
}