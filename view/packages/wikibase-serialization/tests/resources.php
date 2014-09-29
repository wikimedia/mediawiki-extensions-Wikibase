<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	preg_match(
		'+^(.*?)' . preg_quote( DIRECTORY_SEPARATOR ) . '(vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR ) . '(.*)$+',
		__DIR__,
		$remoteExtPathParts
	);

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '../' . $remoteExtPathParts[2]
			. DIRECTORY_SEPARATOR . $remoteExtPathParts[3],
	);

	$modules = array(

		'wikibase.serialization.DeserializerFactory.tests' => $moduleTemplate + array(
			'scripts' => array(
				'DeserializerFactory.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.DeserializerFactory',
			),
		),

		'wikibase.serialization.MockEntity.tests' => $moduleTemplate + array(
			'scripts' => array(
				'MockEntity.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Fingerprint',
				'wikibase.datamodel.MultiTerm',
				'wikibase.datamodel.MultiTermSet',
				'wikibase.datamodel.Term',
				'wikibase.datamodel.TermSet',
				'wikibase.serialization.tests.MockEntity',
			),
		),

		'wikibase.serialization.SerializerFactory.tests' => $moduleTemplate + array(
			'scripts' => array(
				'SerializerFactory.tests.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SerializerFactory',
			),
		),

		'wikibase.serialization.StrategyProvider.tests' => $moduleTemplate + array(
			'scripts' => array(
				'StrategyProvider.tests.js',
			),
			'dependencies' => array(
				'wikibase.serialization.StrategyProvider',
			),
		),

		'wikibase.serialization.tests.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'__namespace.js',
			),
			'dependencies' => array(
				'wikibase.serialization.__namespace',
			),
		),

		'wikibase.serialization.tests.MockEntity' => $moduleTemplate + array(
			'scripts' => array(
				'MockEntity.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Entity',
				'wikibase.serialization.tests.__namespace',
			),
		),

	);

	return array_merge(
		$modules,
		include( __DIR__ . '/Serializers/resources.php' ),
		include( __DIR__ . '/Deserializers/resources.php' )
	);
} );
