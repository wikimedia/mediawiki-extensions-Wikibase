<?php

echo exec( 'composer update' ) . "\n";

if ( !is_readable( __DIR__ . '/../vendor/autoload.php' ) ) {
	die( 'You need to install this package with Composer before you can run the tests' );
}

$loader = require_once( __DIR__ . '/../vendor/autoload.php' );
$loader->addClassMap( array(
	'Tests\Wikibase\DataModel\Serializers\SerializerBaseTest' => __DIR__ . '/unit/Serializers/SerializerBaseTest.php',
	'Tests\Wikibase\DataModel\Deserializers\DeserializerBaseTest' => __DIR__ . '/unit/Serializers/DeserializerBaseTest.php'
) );