<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Extension\MakePdfBook\MakePdfBook;
use MediaWiki\Extension\MakePdfBook\BookSet;
use \OutOfBoundsException;

class SpecialMakePdfBook extends SpecialPage
{
	# Defaults for these config values are defined in extension.json
	private $config;
	private BookSet $bookSet;
	private MakePdfBook $engine;

	public function __construct()
	{
		parent::__construct('MakePdfBook');
		$this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig('MakePdfBook');
		$this->parser = MediaWikiServices::getInstance()->getParser();
		$this->bookSet = new BookSet();
		$this->engine = new MakePdfBook();
	}
	public function execute($subpage) # $subpage parameter included for signature compatibility only
	{
		$parsedUrl = $this->parseMyUrl();
		// return $this->testOutput($parsedUrl['command'], $parsedUrl['target'], $parsedUrl['parameters']);

		try {
			switch ($parsedUrl['command']) {
				case false:
					return $this->buildSpecialPage();
				case "render":
					return $this->servePdf($parsedUrl['target'], $parsedUrl['parameters']);
				case "json":
					return $this->buildJsonResponse($parsedUrl['target'], $parsedUrl['parameters']);
				case "testoutput":
					return $this->testOutput($parsedUrl['command'], $parsedUrl['target'], $parsedUrl['parameters']);
			}
			return $this->buildErrorPage(
				sprintf("%s is not a valid command for MakePdfBook", $parsedUrl['command'])
			);
		} catch (\Exception $e) {
			return $this->buildErrorPage(
				sprintf(
					"Encountered error while attempting to execute MakePdfBook command \'%s\' for target \'%s\'.\n\nError was:\n%s",
					$parsedUrl['command'],
					$parsedUrl['target'],
					$e
				)
			);
		}
	}
	public function getGroupName()
	{
		return 'other';
	}
	public function getDescription()
	{
		return wfMessage("makePdfBook");
	}
	private function getUrlSuffix(): string
	{
		return str_replace(
			$this->getPageTitle()->getLocalURL(), # /index.php/Special:MakePdfBook
			"",
			$this->getRequest()->getRequestURL() # /index.php/Special:MakePdfBook/steve/?testOutput=true&category=bob
		);
	}
	private function parseMyUrl(): array
	{
		$suffix = $this->getUrlSuffix();
		$components = parse_url($suffix);
		if (key_exists("path", $components)) {
			$urlPath = explode('/', $components["path"]);
			# trim empty entries from path array
			# These are caused by leading or trailing slashes in the path
			if ($urlPath[0] == "") {
				unset($urlPath[0]);
			}
			$lastKey = array_key_last($urlPath);
			if ($urlPath[$lastKey] == "") {
				unset($urlPath[$lastKey]);
			}
		} else {
			$urlPath = [];
		}
		if (key_exists("query", $components)) {
			parse_str($components["query"], $params);
		} else {
			$params = [];
		}
		return [
			"command" => key_exists(1, $urlPath) ? strtolower($urlPath[1]) : false,
			"target" => key_exists(2, $urlPath) ? $urlPath[2] : false,
			"parameters" => $params
		];
	}
	private function testOutput(string $command, string $target, array $params)
	{
		$textString = sprintf(
			"command: %s\n\ntarget: %s\n\nparams: %s",
			$command ? $command : "<bool: false>",
			$target ? $target : "<bool: false>",
			var_export($params, return: true)
		);

		$this->getOutput()->addWikiTextAsInterface($textString);
	}
	private function buildJsonResponse($category, $params)
	{
		$jsonText = json_encode(['hello', "world"]);
		if ($category) {
			try {
				$jsonText = json_encode(
					$this->bookSet
						->getBook($category)
						->fetchChapters()
						->fetchTitlePage(),
					JSON_PRETTY_PRINT
				);
			} catch (OutOfBoundsException) {
				$jsonText = sprintf('"error": "\'%s\' is not a valid book"', $category);
			}
		} else {
			$jsonText = json_encode(
				$this->bookSet
					->fetchChapters()
					->fetchTitlePages(),
				JSON_PRETTY_PRINT
			);
		}

		$this->getOutput()->disable();
		header("Content-type: application/json; charset=utf-8");
		print $jsonText;
	}
	private function buildErrorPage($errorMessage)
	{
		$this->getOutput()->addWikiTextAsInterface(
			sprintf(
				"<h2>Error processing MakePdfBook command:</h2><pre>%s</pre>",
				$errorMessage
			)
		);
	}
	private function buildSpecialPage(): void
	{
		$textString = "{| class=\"wikitable\"\n|-\n!Category\n!Pdf handbook\n!Titlepage\n";

		foreach ($this->bookSet->fetchTitlePages()->getBooks(sorted: true) as $book) {
			$textString .= sprintf(
				"|-\n|[%s %s]\n|[%s   pdf]\n",
				$book->title->getFullUrlForRedirect(),
				$book->title->getText(),
				$book->getPdfLink()
			);

			if (empty($book->titlepage)) {
				$textString .= sprintf(
					"| |[[%s_Title_page]] (add <nowiki>[[Category:%s|titlepage]]</nowiki> to bottom of page when you create it)\n",
					$book->title->getDBkey(),
					$book->title->getDBkey()
				);
			} else {
				$textString .= sprintf(
					"||[[%s|%s]]\n",
					$book->titlepage->title->getPrefixedText(),
					$book->titlepage->title->getText()
				);
			}
		}
		$textString .= "|}\n";

		$this->getOutput()->addWikiTextAsInterface($textString);
	}
	private function servePdf($category, $params)
	{
		# get the book and load its content
		$book = $this->bookSet->getBook($category)
			->fetchChapters()
			->fetchTitlePage();

		$cacheHash = $book->getCacheHash();

		# check whether cached version is up to date
		$pdfFile = $this->engine->getCacheFile($cacheHash);

		# If the cache is empty or we're forcing a rebuild, render an up to date pdf
		if (empty($pdfFile) || (key_exists('force', $params) && $params['force'] == 'true')) {
			$pdfFile = $this->engine->render($book);
		}

		# serve the cached pdf
		$this->getOutput()->disable();
		header("Content-type: application/pdf");
		readfile($pdfFile);
	}
}
