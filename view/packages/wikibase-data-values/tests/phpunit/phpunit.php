#!/usr/bin/env php
<?php

require_once( 'PHPUnit/Runner/Version.php' );

if ( PHPUnit_Runner_Version::id() !== '@package_version@'
	&& version_compare( PHPUnit_Runner_Version::id(), '3.7', '<' )
) {
	die( 'PHPUnit 3.7 or later required, you have ' . PHPUnit_Runner_Version::id() . ".\n" );
}
require_once( 'PHPUnit/Autoload.php' );

define( 'DATAVALUES', true );
require_once( __DIR__ . '/../../DataValues.php' );

echo 'Running tests for DataValues version ' . DataValues_VERSION . ".\n";
echo 'phpunit.php --group DataValueExtensions ' . __DIR__ .  "\n";

$runner = new PHPUnit_TextUI_Command();
$runner->run( array(
	'--group',
	'DataValueExtensions',
	__DIR__
) );
