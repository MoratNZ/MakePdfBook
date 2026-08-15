<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\MakePdfBook\Book;

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
    public function render(Book $content): string
    {
        global $makepdfIsDraft;
        $pdfFileName = $this->getCacheFileName($content->getCacheHash());

        $tempDir = $this->createEmptyDirectory($this->getTempDir(), $content->title->getDBkey());

        $content->writeContent($tempDir);

        // Call HTML->PDF pipeline on the contents of tempDir, and write output to pdfFileName
        $cmd = sprintf(
            "python3 %s/../bin/render_pdf_book.py %s %s %s 2>&1",
            dirname(__FILE__),
            escapeshellarg($tempDir),
            escapeshellarg($pdfFileName),
            $makepdfIsDraft ? 'true' : 'false'
        );

        exec($cmd, $outputLines, $exitCode);

        if ($exitCode !== 0) {
            throw new \Exception(implode("\n", $outputLines));
        }
        return $pdfFileName;
    }
    private function getCacheFileName(string $cacheHash): string
    {
        return sprintf(
            "%s/%s.pdf",
            $this->getCacheDir(),
            $cacheHash
        );
    }
    public function getCacheFile(string $cacheHash): ?string
    {
        $cacheFileName = $this->getCacheFileName($cacheHash);
        if (file_exists($cacheFileName) && filemtime($cacheFileName)) {
            return $cacheFileName;
        } else {
            return null;
        }
    }
    private function createEmptyDirectory($baseDirectory, $category)
    {
        $directoryName = "$baseDirectory/MakePdfBook/$category";

        if (is_dir($directoryName)) {
            // The directory exists - empty it
            $files = glob($directoryName . '/*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned

            foreach ($files as $file) {
                unlink($file);
            }
        } else {
            // Create the directory
            mkdir($directoryName, 0777, true);
        }

        return $directoryName;
    }
}