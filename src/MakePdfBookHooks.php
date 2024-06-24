<?php
namespace MediaWiki\Extension\MakePdfBook;

class MakePdfBookHooks
{
    public static function onBeforePageDisplay(&$out)
    {
        $out->addModules('ext.makePdfBook');
        return true;
    }
}