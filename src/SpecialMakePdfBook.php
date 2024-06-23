<?php
namespace MediaWiki\Extension\MakePdfBook;

use MediaWiki\MediaWikiServices;
use MediaWiki\Extension\MakePdfBook\BookSet;
use MediaWiki\SpecialPage\SpecialPage;
use \OutOfBoundsException;

class SpecialMakePdfBook extends SpecialPage
{
	# Defaults for these config values are defined in extension.json
	private $config;
	private BookSet $bookSet;

	public function __construct()
	{
		parent::__construct('MakePdfBook');
		$this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig('MakePdfBook');
		$this->parser = MediaWikiServices::getInstance()->getParser();
		$this->bookSet = new BookSet();
	}
	public function execute($subpage) # $subpage parameter included for signature compatibility only
	{
		$parsedUrl = $this->parseMyUrl();
		// return $this->testOutput($parsedUrl['command'], $parsedUrl['target'], $parsedUrl['parameters']);

		switch ($parsedUrl['command']) {
			case false:
				return $this->buildSpecialPage();
			case "render":
				return $this->renderPdf($parsedUrl['target'], $parsedUrl['parameters']);
			case "json":
				return $this->returnJson($parsedUrl['target'], $parsedUrl['parameters']);
			case "testoutput":
				return $this->testOutput($parsedUrl['command'], $parsedUrl['target'], $parsedUrl['parameters']);
		}
		return $this->buildErrorPage(
			sprintf("%s is not a valid command for MakePdfBook", $parsedUrl['command'])
		);
		// $request = $this->getRequest();
		// $output = $this->getOutput();
		// $this->setHeaders();

		// $cacheFileDir = $this->config->get('MakePdfBookCacheFileDir');

		// $errorText = "";

		// # Get request data 

		// try {
		// 	# Get articles from category
		// 	$articles = $this->getCategoryArticles($category);

		// 	# Get the cache file name
		// 	$cacheFile = $this->getCacheHash($articles) . ".pdf";

		// 	// If the file doesn't exist, render the content now
		// 	if ($forceRebuild || !file_exists("$cacheFileDir/$cacheFile")) {
		// 		$this->renderPdf($cacheFileDir, $cacheFile, $category, $articles);
		// 		if (!file_exists("$cacheFileDir/$cacheFile")) {
		// 			throw new Exception("PDF generation has somehow silently failed. I am confused and ashamed.");
		// 		}
		// 	}
		// 	# Return the PDF, or an error page if generation has failed.
		// } catch (\Throwable $e) {
		// 	$errorText = $e->getMessage();
		// }
		// $output->disable();

		// if ($testPdfOutput) {
		// 	header("Content-type: application/pdf");
		// 	readfile("/home/morat/MakePdfBook/test/marshal_handbook.pdf");
		// } else if ($errorText) {
		// 	header("Content-type: text/html; charset=utf-8");
		// 	print "<html><head></head><body><h1>Error creating PDF book</h1>";
		// 	print "<pre>$errorText</pre>";
		// 	print "</body></html>";
		// } else {
		// 	header("Content-type: application/pdf");
		// 	readfile("$cacheFileDir/$cacheFile");
		// }
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

	private function returnJson($category, $params)
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
	private function getCategoryArticles($category)
	{
		$articles = array();

		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef(DB_REPLICA);

		$result = $dbr->newSelectQueryBuilder()
			->select('cl_from')
			->from('categorylinks')
			->where([
				$dbr->expr("cl_to", "=", $category),
				$dbr->expr("cl_sortkey_prefix", "not like", "%titlepage%"),
			])
			->orderBy('cl_sortkey')
			->caller('MakePdfBook')
			->fetchResultSet();

		foreach ($result as $row) {
			$articles[] = Title::newFromID($row->cl_from);
		}

		return $articles;
	}
	private function getCategoryTitlePage($category)
	{
		$lb = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$dbr = $lb->getConnectionRef(DB_REPLICA);

		$result = $dbr->newSelectQueryBuilder()
			->select('cl_from')
			->from('categorylinks')
			->where(["cl_to = " . $dbr->addQuotes($category), 'cl_sortkey_prefix like "%titlepage%"'])
			->caller('MakePdfBook')
			->fetchResultSet();

		$numRows = $result->numRows();
		if ($numRows == 0) {
			return NULL;
		} else if ($numRows == 1) {
			$row = $result->fetchRow();
			return \Title::newFromID($row[0]);
		} else {
			throw new Exception("There is more than one article in category $category labelled with sort key 'titlepage'. Please trim that down to one.");
		}
	}
	private function getCacheHash($articles)
	{
		$cacheString = '\nFile sig: ' . md5(file_get_contents(__FILE__)); // the contents of the rendering code (this script),
		$cacheString = '\nFile sig: ' . md5(file_get_contents(dirname(__FILE__) . "/../bin/template.tex")); // the contents of the tex template
		foreach ($articles as $art) {
			$cacheString .= "\n" . $art->getPrefixedText() . ': ' . $art->getLatestRevID(); // and the latest revision(s) of the article(s)
		}
		return md5($cacheString);
	}

	private function writeArticleHtmlFile($title, $fileName, $treatAsTitlepage = false)
	{
		global $wgUploadDirectory, $wgScriptPath, $wgUser, $wgServer;

		$scriptPath = $wgServer . $wgScriptPath;
		$opt = ParserOptions::newFromAnon();

		$titleText = $this->stripNameSpace($title->getPrefixedText());

		$fullArticleUrl = $title->getFullUrl();
		$article = new Article($title);
		$text = $article->getPage()->getContent()->getNativeData();

		$htmlText = $this->parser->parse($text, $title, $opt);

		# This section is a bit of a black box, taken from the original PdfBook extension
		####
		$text .= "__NOTOC__";
		#$out = $wgParser->parse($text, $title, $opt, true, true);
		$text = $htmlText->getText($options = [
			'allowTOC' => false,
			'enableSectionEditLinks' => false,
			'unwrap' => false,
			'deduplicateStyles' => true,
		]);
		$urlPath = parse_url($scriptPath, PHP_URL_PATH);
		$imgpath = str_replace('/', '\/', $urlPath . '/' . basename($wgUploadDirectory)); // the image's path
		$text = preg_replace("|(<img[^>]+?src=\"$imgpath)(/.+?>)|", "<img src=\"$wgUploadDirectory$2", $text);

		$titleText = basename($titleText);
		$h1 = "<center><h1>$titleText</h1></center>";
		if ($treatAsTitlepage) {
			$html = utf8_decode("$text\n");
		} else {
			$html = utf8_decode("$h1  $text\n");
		}

		file_put_contents($fileName, $html);
	}
	private function stripNameSpace($titleText)
	{
		// This is a filthy filthy hack to remove namespace labelling from page titles
		// the correct solution is to access the DISPLAYTITLE
		$colonPosition = strpos($titleText, ':');
		if ($colonPosition) {
			return substr($titleText, $colonPosition + 1);
		} else {
			return $titleText;
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
	private function renderPdf($outputDir, $outputFileName, $category, $articles)
	{
		global $wgServer, $wgScriptPath, $makepdfIsDraft;
		$titlePage = $this->getCategoryTitlePage($category);

		$baseTempFileDir = $this->config->get('MakePdfBooktempFileDir');

		// Create the holding directory for our temp files
		$tempFileDir = $this->createEmptyDirectory($baseTempFileDir, $category);

		// Create the content temp files
		if ($titlePage) {
			$titlepageFileName = "$tempFileDir/titlepage.html";
			$this->writeArticleHtmlFile($titlePage, $titlepageFileName, true);
		}

		$articleCount = 0;
		foreach ($articles as $title) {
			$articleCount++;
			$chapterFileName = "$tempFileDir/chapter-$articleCount.html";

			$this->writeArticleHtmlFile($title, $chapterFileName);
		}

		// Copy the template file to the holding dir
		if ($makepdfIsDraft) {
			copy(dirname(__FILE__) . "/../bin/draft_template.tex", "$tempFileDir/template.tex");
		} else {
			copy(dirname(__FILE__) . "/../bin/template.tex", "$tempFileDir/template.tex");
		}


		// Call out to the book assembler
		$cmd = dirname(__FILE__) . "/../bin/makePdfBook.pl $tempFileDir $outputDir/$outputFileName 2>&1";
		$shellResult = shell_exec($cmd);

		if ($shellResult) {
			throw new Exception($shellResult);
		}
	}
}
