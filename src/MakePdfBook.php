<?php
use MediaWiki\MediaWikiServices;

class MakePdfBook
{
    const CACHE_DIR_CONFIG_NAME = "MakePdfBookCacheFileDir";
    const TEMP_DIR_CONFIG_NAME = "MakePdfBooktempFileDir";
    private $config;
    public function __construct()
    {
        $this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig('MakePdfBook');
    }
    public function getCacheDir(): string
    {
        return $this->config->get(self::CACHE_DIR_CONFIG_NAME);
    }
    public function getTempDir(): string
    {
        return $this->config->get(self::TEMP_DIR_CONFIG_NAME);
    }

}