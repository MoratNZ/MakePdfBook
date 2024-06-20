<?php
# environment variables consumed:
# SITE_NAME e.g. SCA_Lochac
# BASE_URL e.g. https://sca.org.nz
# WIKI_EMAIL e.g. wiki@sca.org.nz
# DB_URL e.g. localhost
# DB_PASSWORD- password for database
# DB_SECRET_KEY
# DB_UPGRADE_KEY

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

## Uncomment this to disable output compression
# $wgDisableOutputCompression = true;

$wgSitename = getenv("SITE_NAME");
$wgMetaNamespace = getenv("SITE_NAME");

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "";

## The protocol and server name to use in fully-qualified URLs
$wgServer = getenv("BASE_URL");

## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

## The URL path to the logo.  Make sure you change this from the default,
## or else you'll overwrite your logo when you upgrade!
#$wgLogo = "http://lochac.sca.org/lochac/pics/Lochac304.gif";

## UPO means: this is also a user preference option

$wgEnableEmail = true;
$wgEnableUserEmail = false; # UPO

$wgEmergencyContact = getenv('WIKI_EMAIL');
$wgPasswordSender = getenv('WIKI_EMAIL');

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = true; # UPO
$wgEmailAuthentication = true;

## Database settings
$wgDBtype = "mysql";
$wgDBserver = getenv('DB_URL');
$wgDBname = getenv('DB_NAME');
$wgDBuser = getenv('DB_USER');
$wgDBpassword = getenv("DB_PASSWORD");

# MySQL specific settings
$wgDBprefix = "";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Experimental charset support for MySQL 5.0.
$wgDBmysql5 = false;

## Shared memory settings
$wgMainCacheType = CACHE_NONE;
$wgMemCachedServers = [];

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = false;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale
$wgShellLocale = "C.UTF-8";

## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publically accessible from the web.
#$wgCacheDirectory = "$IP/cache";

# Site language code, should be one of the list in ./languages/data/Names.php
$wgLanguageCode = "en-gb";

$wgSecretKey = getenv('DB_SECRET_KEY');

# Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = "1";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = getenv('UPGRADE_KEY');

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

## Default skin: you can change the default skin. Use the internal symbolic
## names, ie 'vector', 'monobook':
$wgDefaultSkin = "vector";

# Enabled skins.
wfLoadSkin( 'MonoBook' );
wfLoadSkin( 'Vector' );


# Enabled extensions. Most of the extensions are enabled by adding
# wfLoadExtensions('ExtensionName');
wfLoadExtension( 'PdfHandler' );
wfLoadExtension( 'MakePdfBook' );
wfLoadExtension( 'VisualEditor' );
wfLoadExtension( 'WikiEditor' );
wfLoadExtension( 'TemplateData' );

require_once("$IP/extensions/ExternalRedirect/ExternalRedirect.php");
$wgExternalRedirectNsIDs = array(1000, 1002, 1004, 1006, 1008, 1010, 1012);


# Allow uploading of SVGs and render them correctly
$wgFileExtensions[] = 'svg';
$wgAllowTitlesInSVG = true;
$wgSVGNativeRendering = true;

#$wgSVGConverter = 'inkscape';
#$wgSVGConverterPath = '/usr/bin';
$wgTmpDirectory = '/tmp';
$wgMaxShellMemory = '1024000';  #required to get the conversion to run

# End of automatically generated settings.
# Add more configuration options below.

# Debian specific generated settings
# Use system mimetypes
$wgMimeTypeFile = '/etc/mime.types';

# Allow direct setting of page titles, independent of page names 
# This is so namespaces don't have to be present in page titles
$wgAllowDisplayTitle = true;
$wgRestrictDisplayTitle = false;

# Additional namespace definitions
define("NS_ARCHERY",1000);
define("NS_ARCHERY_NOTES",1001);
$wgExtraNamespaces[NS_ARCHERY] = "Archery";
$wgExtraNamespaces[NS_ARCHERY_NOTES] = "Archery_notes";
$wgContentNamespaces[] = NS_ARCHERY;
$wgNamespaceProtection[NS_ARCHERY] = array('editArchery');

define("NS_ARMORED_COMBAT",1002);
define("NS_ARMORED_COMBAT_NOTES",1003);
$wgExtraNamespaces[NS_ARMORED_COMBAT] = "Armored_Combat";
$wgExtraNamespaces[NS_ARMORED_COMBAT_NOTES] = "Armored_Combat_notes";
$wgContentNamespaces[] = NS_ARMORED_COMBAT;
$wgNamespaceProtection[NS_ARMORED_COMBAT] = array('editArmoredCombat');

define("NS_EQUESTRIAN",1004);
define("NS_EQUESTRIAN_NOTES",1005);
$wgExtraNamespaces[NS_EQUESTRIAN] = "Equestrian";
$wgExtraNamespaces[NS_EQUESTRIAN_NOTES] = "Equestrian_notes";
$wgContentNamespaces[] = NS_EQUESTRIAN;
$wgNamespaceProtection[NS_EQUESTRIAN] = array('editEquestrian');

define("NS_FENCING",1006);
define("NS_FENCING_NOTES",1007);
$wgExtraNamespaces[NS_FENCING] = "Fencing";
$wgExtraNamespaces[NS_FENCING_NOTES] = "Fencing_notes";
$wgContentNamespaces[] = NS_FENCING;
$wgNamespaceProtection[NS_FENCING] = array('editFencing');

define("NS_SIEGE",1008);
define("NS_SIEGE_NOTES",1009);
$wgExtraNamespaces[NS_SIEGE] = "Siege";
$wgExtraNamespaces[NS_SIEGE_NOTES] = "Siege_notes";
$wgContentNamespaces[] = NS_SIEGE;
$wgNamespaceProtection[NS_SIEGE] = array('editSiege');

define("NS_THROWN_WEAPONS",1010);
define("NS_THROWN_WEAPONS_NOTES",1011);
$wgExtraNamespaces[NS_THROWN_WEAPONS] = "Thrown_Weapons";
$wgExtraNamespaces[NS_THROWN_WEAPONS_NOTES] = "Thrown_Weapons_notes";
$wgContentNamespaces[] = NS_THROWN_WEAPONS;
$wgNamespaceProtection[NS_THROWN_WEAPONS] = array('editThrownWeapons');

define("NS_YOUTH_MARTIAL",1012);
define("NS_YOUTH_MARTIAL_NOTES",1013);
$wgExtraNamespaces[NS_YOUTH_MARTIAL] = "Youth_Martial";
$wgExtraNamespaces[NS_YOUTH_MARTIAL_NOTES] = "Youth_Martial_notes";
$wgContentNamespaces[] = NS_YOUTH_MARTIAL;
$wgNamespaceProtection[NS_YOUTH_MARTIAL] = array('editYouthMartial');

# User permission settings
# General users can read, but can't edit
# They also can't create their own accounts
$wgGroupPermissions['*']['createaccount'] = false;
$wgGroupPermissions['*']['edit'] = false;

# Logged in users can edit the general namespace
$wgGroupPermissions['user']['read'] = true;
$wgGroupPermissions['user']['edit'] = false;
$wgGroupPermissions['user']['changetags'] = false;
$wgGroupPermissions['user']['applychangetags'] = false;
$wgGroupPermissions['user']['applychangetags'] = false;

$wgGroupPermissions['editor'] = $wgGroupPermissions['user'];
$wgGroupPermissions['editor']['edit'] = true;
$wgGroupPermissions['editor']['changetags'] = true;
$wgGroupPermissions['editor']['applychangetags'] = true;
$wgGroupPermissions['editor']['applychangetags'] = true;

# Namespaces can be editted by their specific editors
$wgGroupPermissions['ArcheryEditor']['editArchery'] = true;
$wgGroupPermissions['ArmoredCombatEditor']['editArmoredCombat'] = true;
$wgGroupPermissions['EquestrianEditor']['editEquestrian'] = true;
$wgGroupPermissions['FencingEditor']['editFencing'] = true;
$wgGroupPermissions['SiegeEditor']['editSiege'] = true;
$wgGroupPermissions['ThrownWeaponsEditor']['editThrownWeapons'] = true;
$wgGroupPermissions['YouthMartialEditor']['editYouthMartial'] = true;

$wgShowExceptionDetails = true;
$wgDebugDumpSql = true;