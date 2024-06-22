<?php
namespace MediaWiki\Extension\MakePdfBook;

class MakePdfBookHooks
{
    public static function addHooks(&$out)
    {
        $out->addModules('ext.makePdfBook');
        return true;
    }
}