<?php

// constants
define( 'CONTENT_MODEL_WIKIBASE_ITEM', "wikibase-item" );
define( 'CONTENT_MODEL_WIKIBASE_PROPERTY', "wikibase-property" );
define( 'WB_NS_ITEM', 123 );

//require_once( __DIR__ . '/../../../tests/unit/initUnitTests.php' );

define( 'MEDIAWIKI', true );
define( 'MW_PHPUNIT_TEST', true );

// Inject test configuration via callback, bypassing LocalSettings.php
define( 'MW_CONFIG_CALLBACK', '\TestSetup::applyInitialConfig' );

$mwBaseDir = __DIR__ . '/../../../../../..';
$wbDir = $mwBaseDir . '/extensions/Wikibase';

// these variables must be defined before setup runs
$GLOBALS['IP'] = $mwBaseDir;
$GLOBALS['wgCommandLineMode'] = true;

require_once $mwBaseDir . '/tests/common/TestSetup.php';
require_once $mwBaseDir . '/includes/Setup.php';
require_once $mwBaseDir . '/tests/common/TestsAutoLoader.php';

require_once $wbDir . '/repo/autoload.php';
require_once $wbDir . '/view/autoload.php';
require_once $wbDir . '/lib/autoload.php';
