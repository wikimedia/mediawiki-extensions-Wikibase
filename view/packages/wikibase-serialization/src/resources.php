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

				'wikibase.serialization.ClaimSerializer',
				'wikibase.serialization.ClaimDeserializer',

				'wikibase.serialization.ClaimGroupSerializer',
				'wikibase.serialization.ClaimGroupDeserializer',

				'wikibase.serialization.ClaimGroupSetSerializer',
				'wikibase.serialization.ClaimGroupSetDeserializer',

				'wikibase.serialization.ClaimListSerializer',
				'wikibase.serialization.ClaimListDeserializer',

				'wikibase.serialization.EntitySerializer',
				'wikibase.serialization.EntityDeserializer',

				'wikibase.serialization.EntityIdSerializer',
				'wikibase.serialization.EntityIdDeserializer',

				'wikibase.serialization.FingerprintSerializer',
				'wikibase.serialization.FingerprintDeserializer',

				'wikibase.serialization.MultiTermSerializer',
				'wikibase.serialization.MultiTermDeserializer',

				'wikibase.serialization.MultiTermSetSerializer',
				'wikibase.serialization.MultiTermSetDeserializer',

				'wikibase.serialization.ReferenceSerializer',
				'wikibase.serialization.ReferenceDeserializer',

				'wikibase.serialization.ReferenceListSerializer',
				'wikibase.serialization.ReferenceListDeserializer',

				'wikibase.serialization.SiteLinkSerializer',
				'wikibase.serialization.SiteLinkDeserializer',

				'wikibase.serialization.SiteLinkSetSerializer',
				'wikibase.serialization.SiteLinkSetDeserializer',

				'wikibase.serialization.SnakSerializer',
				'wikibase.serialization.SnakDeserializer',

				'wikibase.serialization.SnakListSerializer',
				'wikibase.serialization.SnakListDeserializer',

				'wikibase.serialization.StatementSerializer',
				'wikibase.serialization.StatementDeserializer',

				'wikibase.serialization.StatementGroupSerializer',
				'wikibase.serialization.StatementGroupDeserializer',

				'wikibase.serialization.StatementGroupSetSerializer',
				'wikibase.serialization.StatementGroupSetDeserializer',

				'wikibase.serialization.StatementListSerializer',
				'wikibase.serialization.StatementListDeserializer',

				'wikibase.serialization.TermSerializer',
				'wikibase.serialization.TermDeserializer',

				'wikibase.serialization.TermSetSerializer',
				'wikibase.serialization.TermSetDeserializer',
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
			),
		),

		'wikibase.serialization.SerializerFactory' => $moduleTemplate + array(
			'scripts' => array(
				'SerializerFactory.js',
			),
			'dependencies' => array(
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

	);

	return array_merge(
		$modules,
		include( __DIR__ . '/Serializers/resources.php' ),
		include( __DIR__ . '/Deserializers/resources.php' )
	);
} );
