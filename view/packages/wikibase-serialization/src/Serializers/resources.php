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

		'wikibase.serialization.ClaimListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ClaimList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.ClaimsSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimsSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.ClaimSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'ClaimSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Claim',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.SnakListSerializer',
				'wikibase.serialization.SnakSerializer',
			),
		),

		'wikibase.serialization.EntityIdSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'EntityIdSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.EntitySerializer' => $moduleTemplate + array(
			'scripts' => array(
				'EntitySerializer.js',
				'EntitySerializer.itemExpert.js',
				'EntitySerializer.propertyExpert.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimsSerializer',
				'wikibase.serialization.MultilingualSerializer',
				'wikibase.serialization.SiteLinkSerializer',
				'wikibase.serialization.Unserializer',
			),
		),

		'wikibase.serialization.FingerprintSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'FingerprintSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Fingerprint',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermListSerializer',
				'wikibase.serialization.TermGroupListSerializer',
			),
		),

		'wikibase.serialization.MultilingualSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'MultilingualSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.ReferenceListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.ReferenceList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ReferenceSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.ReferenceSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'ReferenceSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakListSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.Serializer' => $moduleTemplate + array(
			'scripts' => array(
				'Serializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.serialization.__namespace',
			),
		),

		'wikibase.serialization.SiteLinkListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.SiteLinkList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SiteLinkSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.SiteLinkSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'SiteLinkSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.SnakListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'SnakListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.SnakSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.SnakSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'SnakSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Snak',
				'wikibase.datamodel.PropertyValueSnak',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.StatementListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.StatementList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.StatementSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.StatementSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'StatementSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Statement',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.ClaimSerializer',
				'wikibase.serialization.ReferenceListSerializer',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.TermGroupListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermGroupListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.TermGroupList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermGroupSerializer',
			),
		),

		'wikibase.serialization.TermGroupSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermGroupSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.TermGroup',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

		'wikibase.serialization.TermListSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermListSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.TermList',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
				'wikibase.serialization.TermSerializer',
			),
		),

		'wikibase.serialization.TermSerializer' => $moduleTemplate + array(
			'scripts' => array(
				'TermSerializer.js',
			),
			'dependencies' => array(
				'util.inherit',
				'wikibase.datamodel.Term',
				'wikibase.serialization.__namespace',
				'wikibase.serialization.Serializer',
			),
		),

	);

	return $modules;
} );
