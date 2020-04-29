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
		$forceRebuild = $request->getText('force');
		$testPdfOutput = $request->getText('testPdfOutput');

		# Check that this is a valid category, and get its DB ID if it is.
		# We don't actually care about the id, but want to use the exceptions for user feedback
		# TODO if a category isn't set, dynamically build a page to offer all possible categories
		if(! $category){
			return $this->generateCategoryListPage();
		}
		try{
			$category_id = $this->getCategoryId($category);
			
			# Get articles from category
			$articles = $this->getCategoryArticles($category);
			
			# Get the cache file name
			$cacheFile = $this->getCacheHash($articles).".pdf";

			// If the file doesn't exist, render the content now
			if ($forceRebuild || !file_exists("$cacheFileDir/$cacheFile")) {
				$this->renderPdf($cacheFileDir, $cacheFile, $category, $articles);
				if(!file_exists("$cacheFileDir/$cacheFile")){
					throw new Exception("PDF generation has somehow silently failed. I am confused and ashamed.");
				}
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
			print "<pre>$errorText</pre>";
			print "</body></html>";
		} else {
			header( "Content-type: application/pdf" );
			readfile("$cacheFileDir/$cacheFile");
		}
	}
	function generateCategoryListPage(){
		global $wgServer, $wgScriptPath;
		$request = $this->getRequest();
		$output = $this->getOutput();

		$db = wfGetDB( DB_REPLICA );

		$result = $db->select(
			'category',
			'cat_title',
			'cat_pages > 0'
		);
		while ($row = $db->fetchRow($result)){
			$category = $row[0];

			$textString = "[$wgServer$wgScriptPath/index.php/Special:MakePdfBook?category=$category   $category ]";

			if(method_exists($output, "addWikiTextAsInterface")){
				$output->addWikiTextAsInterface($textString); # mediawiki >= 1.34
			} else {
				$output->addWikiText($textString);
			}
			
		}
	}
	function getCategoryArticles($category){
		$db = wfGetDB( DB_REPLICA );
		$articles = array();
		$result = $db->select(
			'categorylinks',
			'cl_from',
			["cl_to = ".$db->addQuotes($category), 'cl_sortkey_prefix != "titlepage"'],
			'MakePdfBook',
			array('ORDER BY' => 'cl_sortkey')
		);
		while ($row = $db->fetchRow($result)){ 
			$articles[] = Title::newFromID($row[0]);
		}
		return $articles;
	}
	function getCategoryTitlePage($category){
		$db = wfGetDB( DB_REPLICA );
		$result = $db->select(
			'categorylinks',
			'cl_from',
			["cl_to = ".$db->addQuotes($category), 'cl_sortkey_prefix = "titlepage"'],
			'MakePdfBook'
		);
		$numRows = $result->numRows();
		if($numRows == 0){
			return NULL;
		} else if ($numRows == 1){
			$row = $result->fetchRow();
			return Title::newFromID($row[0]);
		} else {
			throw new Exception("There is more than one article in category $category labelled with sort key 'titlepage'. Please trim that down to one.");
		}
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
	function getCacheHash($articles){
		$cacheString = '\nFile sig: ' . md5(file_get_contents(__FILE__)); // the contents of the rendering code (this script),
		$cacheString = '\nFile sig: ' . md5(file_get_contents(dirname(__FILE__)."/../bin/template.tex")); // the contents of the tex template
		foreach ($articles as $art){
			$cacheString .= "\n" . $art->getPrefixedText() . ': ' . $art->getLatestRevID(); // and the latest revision(s) of the article(s)
		}
		return md5($cacheString);
	}

	function writeArticleHtmlFile($title, $fileName, $treatAsTitlepage=false){
		global $wgUploadDirectory, $wgScriptPath, $wgUser, $wgServer;
		
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

		$titleText = basename($titleText);
		$h1 =  "<center><h1>$titleText</h1></center>";
		if($treatAsTitlepage){
			$html  = utf8_decode("$text\n");
		} else {
			$html  = utf8_decode("$h1  $text\n");
		}

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
	function createEmptyDirectory($baseDirectory, $category){
		$directoryName = "$baseDirectory/MakePdfBook/$category";

		if(is_dir($directoryName)){
			// The directory exists - empty it
			$files = glob( $directoryName . '/*', GLOB_MARK ); //GLOB_MARK adds a slash to directories returned

			foreach( $files as $file ){
				unlink( $file );
			}
		} else {
			// Create the directory
			mkdir($directoryName, 0777, true);
		} 

		return $directoryName;
	}
	function renderPdf($outputDir, $outputFileName, $category, $articles){
		global $wgServer, $wgScriptPath;
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'MakePdfBook' );
		$titlePage = $this->getCategoryTitlePage($category);
		
		$baseTempFileDir = $config->get( 'MakePdfBooktempFileDir' );

		// Create the holding directory for our temp files
		$tempFileDir = $this->createEmptyDirectory($baseTempFileDir, $category);

		// Create the content temp files
		if ($titlePage) {;
			$titlepageFileName = "$tempFileDir/titlepage.html";
			$this->writeArticleHtmlFile($titlePage, $titlepageFileName, true);
		}

		$articleCount = 0;
		foreach( $articles as $title){
			$articleCount++;
			$chapterFileName = "$tempFileDir/chapter-$articleCount.html";

			$this->writeArticleHtmlFile($title, $chapterFileName);
		}

		// Copy the template file to the holding dir
		copy(dirname(__FILE__)."/../bin/template.tex", "$tempFileDir/template.tex");

		// Call out to the book assembler
		$cmd = dirname(__FILE__)."/../bin/makePdfBook.pl $tempFileDir $outputDir/$outputFileName 2>&1";
		$shellResult = shell_exec($cmd);

		if($shellResult){
			throw new Exception($shellResult);
		} 

}
}
?>