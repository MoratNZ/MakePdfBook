<?php
namespace MediaWiki\Extension\MyExtension;

use MediaWiki\MediaWikiServices;


class SpecialMakePdfBook extends \SpecialPage
{
	# Defaults for these config values are defined in extension.json
	private $config;

	public function __construct()
	{
		parent::__construct('MakePdfBook');
		$this->config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig('MakePdfBook');
		$this->parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();

	}
	public function execute($par)
	{
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();

		$cacheFileDir = $this->config->get('MakePdfBookCacheFileDir');

		$errorText = "";

		# Get request data 
		$category = $request->getText('category');
		$json = $request->getText('json');
		$forceRebuild = $request->getText('force');
		$testPdfOutput = $request->getText('testPdfOutput');

		# Check that this is a valid category, and get its DB ID if it is.
		# We don't actually care about the id, but want to use the exceptions for user feedback

		if ($json) {
			return $this->returnCategoryJson($category);
		} elseif (!$category) {
			return $this->generateCategoryListPage();
		}
		try {
			# Get articles from category
			$articles = $this->getCategoryArticles($category);

			# Get the cache file name
			$cacheFile = $this->getCacheHash($articles) . ".pdf";

			// If the file doesn't exist, render the content now
			if ($forceRebuild || !file_exists("$cacheFileDir/$cacheFile")) {
				$this->renderPdf($cacheFileDir, $cacheFile, $category, $articles);
				if (!file_exists("$cacheFileDir/$cacheFile")) {
					throw new Exception("PDF generation has somehow silently failed. I am confused and ashamed.");
				}
			}
			# Return the PDF, or an error page if generation has failed.
		} catch (\Throwable $e) {
			$errorText = $e->getMessage();
		}
		$output->disable();

		if ($testPdfOutput) {
			header("Content-type: application/pdf");
			readfile("/home/morat/MakePdfBook/test/marshal_handbook.pdf");
		} else if ($errorText) {
			header("Content-type: text/html; charset=utf-8");
			print "<html><head></head><body><h1>Error creating PDF book</h1>";
			print "<pre>$errorText</pre>";
			print "</body></html>";
		} else {
			header("Content-type: application/pdf");
			readfile("$cacheFileDir/$cacheFile");
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
	private function returnCategoryJson($category)
	{
		$categories = $this->getBooks($category);
		arsort($categories);

		$output = $this->getOutput();
		$output->disable();

		header("Content-type: application/json; charset=utf-8");
		print json_encode($categories, JSON_PRETTY_PRINT);
	}
	private function generateCategoryListPage()
	{
		global $wgServer, $wgScriptPath;
		$handbooks = $this->getBooks();

		arsort($handbooks);

		$textString = "{| class=\"wikitable\"\n|-\n!Category\n!Pdf handbook\n!Titlepage\n";

		foreach ($handbooks as $category => $handbook) {
			$textString .= "|-\n" .
				"|[$wgServer$wgScriptPath/index.php/Category:$category  $category]\n" .
				"|[$wgServer$wgScriptPath/index.php/Special:MakePdfBook?category=$category   pdf]\n";

			if (array_key_exists('titlepage', $handbook)) {
				$textString .= "||[[" . $handbook['titlepage']['title'] . "]]\n";
			} else {
				$textString .= "| |[[" . $category . "_Title_page]] (add <nowiki>[[Category:" . $category . "|titlepage]]</nowiki> to bottom of page when you create it)\n";
			}
		}
		$textString .= "|}\n";

		$output = $this->getOutput();
		$output->addWikiTextAsInterface($textString);
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
	private function getBooks($category = false)
	{
		$articles = array();

		$instance = MediaWikiServices::getInstance();
		$lb = $instance->getDBLoadBalancer();
		$dbr = $lb->getConnection(DB_REPLICA);

		$whereClause = [
			"cat_title like '%book%'",
			"cl_sortkey_prefix not like '%titlepage%'",
		];
		if ($category) {
			$whereClause[] = "cat_title =\"$category\""; # TODO: replace with an expr() call once we get MW 1.42
		}

		# Get the non-titlepage pages
		$query = $dbr->newSelectQueryBuilder()
			->select([
				'cat_title',
				'page_id',
				'cl_sortkey_prefix',
			])
			->from('page')
			->join('categorylinks', null, 'page_id=cl_from')
			->join('category', null, 'cl_to=cat_title')
			->where($whereClause)
			->caller('MakePdfBook');
		$result = $query->fetchResultSet();


		foreach ($result as $row) {
			$category = $row->cat_title;
			$page_id = $row->page_id;
			$sortKey = $row->cl_sortkey_prefix;

			$page = Title::newFromID($page_id);

			if (!array_key_exists($category, $articles)) {
				$articles[$category] = [
					'title' => $category,
					'chapters' => []
				];
			}

			$articles[$category]['chapters'][$sortKey] = [
				'title' => $page->getText(),
				'sortKey' => $sortKey,
				'url' => $page->getFullUrl(),
			];
		}
		# Get the titlepage pages
		#
		# This is being done like this so that we can have fuzzy titlepage
		# labelling in the wiki, but precise identification of titlepages here

		$whereClause[1] = "cl_sortkey_prefix like '%titlepage%'";
		$query = $dbr->newSelectQueryBuilder()
			->select([
				'cat_title',
				'page_id',
				'cl_sortkey_prefix',
			])
			->from('page')
			->join('categorylinks', null, 'page_id=cl_from')
			->join('category', null, 'cl_to=cat_title')
			->where($whereClause)
			->caller('MakePdfBook');

		$result = $query->fetchResultSet();

		foreach ($result as $row) {
			$category = $row->cat_title;
			$page_id = $row->page_id;
			$sortKey = $row->cl_sortkey_prefix;

			$page = Title::newFromID($page_id);

			$titlePage = [
				'title' => $page->getText(),
				'url' => $page->getFullUrl(),
			];
			if (!array_key_exists($category, $articles)) {
				$articles[$category] = [
					'title' => $category,
					'chapters' => [],
					'titlepage' => $titlePage
				];
			} else {
				$articles[$category]['titlepage'] = $titlePage;
			}
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
			return Title::newFromID($row[0]);
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
