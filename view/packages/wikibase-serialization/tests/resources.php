<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	$modules = [

		'wikibase.serialization.DeserializerFactory.tests' => $moduleTemplate + [
			'scripts' => [
				'DeserializerFactory.tests.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.DeserializerFactory',
			],
		],

		'wikibase.serialization.MockEntity.tests' => $moduleTemplate + [
			'scripts' => [
				'MockEntity.tests.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermMap',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermMap',
				'wikibase.serialization.tests.MockEntity',
			],
		],

		'wikibase.serialization.SerializerFactory.tests' => $moduleTemplate + [
			'scripts' => [
				'SerializerFactory.tests.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SerializerFactory',
			],
		],

		'wikibase.serialization.StrategyProvider.tests' => $moduleTemplate + [
			'scripts' => [
				'StrategyProvider.tests.js',
			],
			'dependencies' => [
				'wikibase.serialization.StrategyProvider',
			],
		],

		'wikibase.serialization.tests.__namespace' => $moduleTemplate + [
			'scripts' => [
				'__namespace.js',
			],
			'dependencies' => [
				'wikibase.serialization.__namespace',
			],
		],

		'wikibase.serialization.tests.MockEntity' => $moduleTemplate + [
			'scripts' => [
				'MockEntity.js',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.datamodel.Entity',
				'wikibase.serialization.tests.__namespace',
			],
		],

	];

	return array_merge(
		$modules,
		include __DIR__ . '/Serializers/resources.php',
		include __DIR__ . '/Deserializers/resources.php'
	);
} );
