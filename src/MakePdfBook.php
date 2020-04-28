<?php
use MediaWiki\MediaWikiServices;
class SpecialMakePdfBook extends SpecialPage {

	function __construct() {
		parent::__construct( 'MakePdfBook' );
		$this->parser = \MediaWiki\MediaWikiServices::getInstance()->getParser();
	}
	function getGroupName(){
			return 'other';
	}
	function getDescription() {
		return "Make PDF book";
	}

	function execute( $par ) {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$this->setHeaders();


		# Defaults for these config values are defined in extension.json
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'MakePdfBook' );
		
		$tempFileDir = $config->get( 'MakePdfBooktempFileDir' );
		$cacheFileDir = $config->get( 'MakePdfBookCacheFileDir' );

		$errorText = "";

		# Get request data 
		$category = $request->getText( 'category' );
		$titlePage = $request->getText('titlePage');
		$forceRebuild = $request->getText('force');
		$testPdfOutput = $request->getText('testPdfOutput');

		# Check that this is a valid category, and get its DB ID if it is.
		# We don't actually care about the id, but want to use the exceptions for user feedback
		try{
		$category_id = $this->getCategoryId($category);
		
		# Get articles from category
		$articles = $this->getCategoryArticles($category);
		
		# Get the cache file name
		$cacheFile = $this->getCacheHash($articles, $titlePage);

		// If the file doesn't exist, render the content now
		if ($forceRebuild || !file_exists($cacheDir . $cacheFile . ".pdf")) {
			$this->renderPdf($cacheFileDir, $cacheFile, $category, $articles, $titlePage);
		}

		# Return the PDF, or an error page if generation has failed.
	} catch (Exception $e){
		$errorText = $e->getMessage();
	}
		$output->disable();

		if($testPdfOutput){
			header( "Content-type: application/pdf" );
			readfile("/home/morat/MakePdfBook/test/marshal_handbook.pdf");
		} else if ($errorText){
			header( "Content-type: text/html; charset=utf-8" );
			print "<html><head></head><body><h1>Error creating PDF book</h1>";
			print "<p>$errorText</p>";
			print "</body></html>";
		} else {
			header( "Content-type: application/pdf" );
			readfile("$cacheDir/$cacheFile.pdf");

			// header( "Content-type: text/html; charset=utf-8" );
			// print "<html><head></head><body><h1>It Worked!</h1>";
			// print "<p>category = [$category]</p>";
			// print "<p>titlePage = [$titlePage]</p>";
			// print "<p>template = [$template]</p>";
			// print "<p>forceRebuild = [$forceRebuild]</p>";
			// print "</body></html>";
			// print "$second_query";

			// print "<p>There were $article_count articles:</p><ul>";
			// foreach($articles as $art){
			// 	print "<li>".$art->getPrefixedText()."</li>";
			// }
			// print "</ul>";
			#$output->addWikiTextAsInterface( $wikitext );
		}
	}
	function getCategoryArticles($category){
		$db = wfGetDB( DB_REPLICA );
		$articles = array();
		$result = $db->select(
			'categorylinks',
			'cl_from',
			"cl_to = ".$db->addQuotes($category),
			'pandocPdfBook',
			array('ORDER BY' => 'cl_sortkey')
		);
		while ($row = $db->fetchRow($result)){ 
			$articles[] = Title::newFromID($row[0]);
		}
		return $articles;
	}
	function getCategoryId($category){
		$db = wfGetDB( DB_REPLICA );
		$result = $db->select(
			'category',
			'cat_id',
			"cat_title=".$db->addQuotes($category),
			"MakePdfBook"
		);
		$numRows = $result->numRows();

		if($numRows == 0){
			throw new Exception("$category is not a valid category.");
		} else if ($numRows == 1){
			$row = $result->fetchRow();
			return $row[0];
		} else {
			throw new Exception("We got more than one DB match for category $category. That should never happen.");
		}
	}

	function getCacheHash($articles, $titlePage){
		$cacheString = '\nFile sig: ' . md5(file_get_contents(__FILE__)); // the contents of the rendering code (this script),
		
		foreach ($articles as $art){
			$cacheString .= "\n" . $art->getPrefixedText() . ': ' . $art->getLatestRevID(); // and the latest revision(s) of the article(s)
		}
		$cacheString .= $titlePage ? "\n" . $titlePage . ": " . Title::newFromText($titlePage)->getLatestRevID() : "";
		return md5($cacheString);
	}
	function writeTitlePageTexFile($titlePage, $fileName){
		$titlePageObject = Title::newFromText($titlePage);
		$titlePageId = $titlePageObject->getArticleID();

		$titlePageArticle = Article::newFromId($titlePageId);

		$text = $titlePageArticle->getPage()->getContent()->getNativeData();

		$text = utf8_decode($text);

		file_put_contents($fileName, $text);

	}
	function writeArticleWikitextFile($title, $fileName){
		$scriptPath = $wgServer . $wgScriptPath;
		$opt = ParserOptions::newFromUser($wgUser);

		$titleText = $this->stripNameSpace($title->getPrefixedText());
		
		$fullArticleUrl = $title->getFullUrl();
		$article = new Article($title);
		$text = $article->getPage()->getContent()->getNativeData();

		file_put_contents($fileName, $text);		
	}
	function writeArticleHtmlFile($title, $fileName){
		global $wgUploadDirectory, $wgScriptPath, $wgUser;
		
		$scriptPath = $wgServer . $wgScriptPath;
		$opt = ParserOptions::newFromUser($wgUser);

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
		$pUrl = parse_url($scriptPath);
		$imgpath = str_replace('/', '\/', $pUrl['path'] . '/' . basename($wgUploadDirectory)); // the image's path
		$text = preg_replace("|(<img[^>]+?src=\"$imgpath)(/.+?>)|", "<img src=\"$wgUploadDirectory$2", $text);
		$text = preg_replace("|<div\s*class=['\"]?noprint[\"']?>.+?</div>|s", "", $text); // non-printable areas
		$text = preg_replace("|@{4}([^@]+?)@{4}|s", "<!--$1-->", $text);                  // HTML comments hack
		$text = preg_replace_callback(
			"|<span[^>]+class=\"mw-headline\"[^>]*>(.+?)</span>|",
			function ($m) {
				return preg_match('|id="(.+?)"|', $m[0], $n) ? "<a name=\"$n[1]\">$m[1]</a>" : $m[0];
			},
			$text
		); // Make the doc heading spans in to A tags

		#######

		$titleText = basename($titleText);
		$h1 =  "<center><h1>$titleText</h1></center>";

		$html  = utf8_decode("$h1  $text\n");

		file_put_contents($fileName, $html);
	}
	function stripNameSpace($titleText){
		// This is a filthy filthy hack to remove namespace labelling from page titles
		// the correct solution is to access the DISPLAYTITLE
		$colonPosition = strpos($titleText, ':');
		if ($colonPosition) {
			return substr($titleText, $colonPosition + 1);
		} else {
			return $titleText;
		}
	}
	function renderPdf($outputDir, $fileName, $category, $articles, $titlePage){
		global $wgServer, $wgScriptPath;
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'MakePdfBook' );
		
		$tempFileDir = $config->get( 'MakePdfBooktempFileDir' );

		// Create the content temp files
		if ($titlePage) {
			$this->writeTitlePageTexFile($titlePage, "$tempFileDir/pandoc-$category-titlepage.tex");
			$titleOption = "-B $titlepageFile";
		} else {
			$titleOption = "";
		}

		$articleCount = 0;
		$pandocFilesString = "";
		foreach( $articles as $title){
			$articleCount++;
			$fileName = "$tempFileDir/MakePdfBook-$category-chapter-$articleCount.html";
			$pandocFilesString .= "$fileName ";

			$this->writeArticleHtmlFile($title, $fileName);
		}

		// Build the pandoc command
		$cover = $titlePage ? "cover $titlepageFile"  : "";

		$tempTexFile = "$tempFileDir/pandoc-$category-tmpFile.tex";
		$templateFile = dirname(__FILE__)."/../bin/template.tex";
		$cmd = "PATH=/usr/bin/: pandoc -s -f html -t latex $titleOption --templateFile  $templateFile -s $pandocFileString -o $tempTexFile";

		throw new Exception($cmd);
		// Build the tex file for the book
		$shellResult = shell_exec("$cmd ");

		// Run modification scripts on the tex output
		$cmd = escapeshellcmd("$pandocPath/texFix.pl $tempTexFile 2>>$debugFileDir/pandocpdefbook.errlog");
		$shellResult = shell_exec("$cmd ");

		// Generate the pdf from the modified tex
		$cmd = "PATH=/usr/bin/: pdflatex -jobname $fileName -output-directory $outputDir $tempTexFile";
		$shellResult1 = shell_exec("$cmd ");
		$shellResult2 = shell_exec("$cmd ");
		$shellResult3 = shell_exec("$cmd "); # generation run twice to ensure Table of Contents generates correctly

}
}
?>