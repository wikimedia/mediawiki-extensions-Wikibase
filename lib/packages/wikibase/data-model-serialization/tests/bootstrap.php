<?php

if ( PHP_SAPI !== 'cli' ) {
	die( 'Not an entry point' );
}

error_reporting( E_ALL | E_STRICT );
ini_set( 'display_errors', 1 );

if ( !is_readable( __DIR__ . '/../vendor/autoload.php' ) ) {
	die( 'You need to install this package with Composer before you can run the tests' );
}

$loader = require_once( __DIR__ . '/../vendor/autoload.php' );
$loader->addClassMap( array(
	'Tests\Wikibase\DataModel\Serializers\SerializerBaseTest' => __DIR__ . '/unit/Serializers/SerializerBaseTest.php',
	'Tests\Wikibase\DataModel\Deserializers\DeserializerBaseTest' => __DIR__ . '/unit/Deserializers/DeserializerBaseTest.php'
) );
