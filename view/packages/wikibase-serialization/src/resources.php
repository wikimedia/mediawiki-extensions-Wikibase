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

		'wikibase.serialization' => $moduleTemplate + array(
			'scripts' => array(
				'init.js',
			),
			'dependencies' => array(
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.DeserializerFactory',
				'wikibase.serialization.SerializerFactory',
			),
		),

		'wikibase.serialization.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'__namespace.js',
			),
			'dependencies' => array(
				'wikibase',
			),
		),

		'wikibase.serialization.DeserializerFactory' => $moduleTemplate + array(
			'scripts' => array(
				'DeserializerFactory.js',
			),
			'dependencies' => array(
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Deserializer',
				'wikibase.serialization.StrategyProvider',

				'wikibase.serialization.ClaimDeserializer',
				'wikibase.serialization.ClaimGroupDeserializer',
				'wikibase.serialization.ClaimGroupSetDeserializer',
				'wikibase.serialization.ClaimListDeserializer',
				'wikibase.serialization.EntityDeserializer',
				'wikibase.serialization.EntityIdDeserializer',
				'wikibase.serialization.FingerprintDeserializer',
				'wikibase.serialization.MultiTermDeserializer',
				'wikibase.serialization.MultiTermSetDeserializer',
				'wikibase.serialization.ReferenceDeserializer',
				'wikibase.serialization.ReferenceListDeserializer',
				'wikibase.serialization.SiteLinkDeserializer',
				'wikibase.serialization.SiteLinkSetDeserializer',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakListDeserializer',
				'wikibase.serialization.StatementDeserializer',
				'wikibase.serialization.StatementGroupDeserializer',
				'wikibase.serialization.StatementGroupSetDeserializer',
				'wikibase.serialization.StatementListDeserializer',
				'wikibase.serialization.TermDeserializer',
				'wikibase.serialization.TermSetDeserializer',
			),
		),

		'wikibase.serialization.SerializerFactory' => $moduleTemplate + array(
			'scripts' => array(
				'SerializerFactory.js',
			),
			'dependencies' => array(
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.StrategyProvider',

				'wikibase.serialization.ClaimGroupSerializer',
				'wikibase.serialization.ClaimGroupSetSerializer',
				'wikibase.serialization.ClaimListSerializer',
				'wikibase.serialization.ClaimSerializer',
				'wikibase.serialization.EntityIdSerializer',
				'wikibase.serialization.EntitySerializer',
				'wikibase.serialization.FingerprintSerializer',
				'wikibase.serialization.MultiTermSerializer',
				'wikibase.serialization.MultiTermSetSerializer',
				'wikibase.serialization.ReferenceListSerializer',
				'wikibase.serialization.ReferenceSerializer',
				'wikibase.serialization.SiteLinkSerializer',
				'wikibase.serialization.SiteLinkSetSerializer',
				'wikibase.serialization.SnakListSerializer',
				'wikibase.serialization.SnakSerializer',
				'wikibase.serialization.StatementGroupSerializer',
				'wikibase.serialization.StatementGroupSetSerializer',
				'wikibase.serialization.StatementListSerializer',
				'wikibase.serialization.StatementSerializer',
				'wikibase.serialization.TermSerializer',
				'wikibase.serialization.TermSetSerializer',
			),
		),

		'wikibase.serialization.StrategyProvider' => $moduleTemplate + array(
			'scripts' => array(
				'StrategyProvider.js',
			),
			'dependencies' => array(
				'wikibase.serialization.__namespace',
			),
		),

	);

	return array_merge(
		$modules,
		include( __DIR__ . '/Serializers/resources.php' ),
		include( __DIR__ . '/Deserializers/resources.php' )
	);
} );
