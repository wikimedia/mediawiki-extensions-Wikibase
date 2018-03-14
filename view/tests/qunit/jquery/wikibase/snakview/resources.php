<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/tests/qunit/jquery/wikibase/snakview',
	];

	$resources = [

		'jquery.wikibase.snakview.tests' => $moduleTemplate + [
			'scripts' => [
				'snakview.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.snakview',
				'wikibase.datamodel.Property',
				'wikibase.datamodel.PropertyNoValueSnak',
				'wikibase.datamodel.PropertySomeValueSnak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
			],
		],

	];

	return $resources;
} );
