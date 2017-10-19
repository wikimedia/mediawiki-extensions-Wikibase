<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . 'wikibase-serialization' . DIRECTORY_SEPARATOR . 'src'
		. DIRECTORY_SEPARATOR . 'Serializers';

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', $dir, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	$modules = [
		'wikibase.serialization.ClaimSerializer' => $moduleTemplate + [
				'scripts' => [
					'ClaimSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Claim',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.SnakListSerializer',
					'wikibase.serialization.SnakSerializer',
				],
			],

		'wikibase.serialization.EntitySerializer' => $moduleTemplate + [
				'scripts' => [
					'EntitySerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Entity',
					'wikibase.datamodel.Item',
					'wikibase.datamodel.Property',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.ItemSerializer',
					'wikibase.serialization.PropertySerializer',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.StrategyProvider',
				],
			],

		'wikibase.serialization.FingerprintSerializer' => $moduleTemplate + [
				'scripts' => [
					'FingerprintSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Fingerprint',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.MultiTermMapSerializer',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.TermMapSerializer',
				],
			],

		'wikibase.serialization.ItemSerializer' => $moduleTemplate + [
				'scripts' => [
					'ItemSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Item',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.FingerprintSerializer',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.SiteLinkSetSerializer',
					'wikibase.serialization.StatementGroupSetSerializer',
				],
			],

		'wikibase.serialization.MultiTermMapSerializer' => $moduleTemplate + [
				'scripts' => [
					'MultiTermMapSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.MultiTermMap',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.MultiTermSerializer',
					'wikibase.serialization.Serializer',
				],
			],

		'wikibase.serialization.MultiTermSerializer' => $moduleTemplate + [
				'scripts' => [
					'MultiTermSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.MultiTerm',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
				],
			],

		'wikibase.serialization.PropertySerializer' => $moduleTemplate + [
				'scripts' => [
					'PropertySerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Item',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.FingerprintSerializer',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.StatementGroupSetSerializer',
				],
			],

		'wikibase.serialization.ReferenceListSerializer' => $moduleTemplate + [
				'scripts' => [
					'ReferenceListSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.ReferenceList',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.ReferenceSerializer',
					'wikibase.serialization.Serializer',
				],
			],

		'wikibase.serialization.ReferenceSerializer' => $moduleTemplate + [
				'scripts' => [
					'ReferenceSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Reference',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.SnakListSerializer',
				],
			],

		'wikibase.serialization.Serializer' => $moduleTemplate + [
				'scripts' => [
					'Serializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.serialization.__namespace',
				],
			],

		'wikibase.serialization.SiteLinkSerializer' => $moduleTemplate + [
				'scripts' => [
					'SiteLinkSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.SiteLink',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
				],
			],

		'wikibase.serialization.SiteLinkSetSerializer' => $moduleTemplate + [
				'scripts' => [
					'SiteLinkSetSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.SiteLinkSet',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.SiteLinkSerializer',
				],
			],

		'wikibase.serialization.SnakListSerializer' => $moduleTemplate + [
				'scripts' => [
					'SnakListSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.SnakList',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.SnakSerializer',
				],
			],

		'wikibase.serialization.SnakSerializer' => $moduleTemplate + [
				'scripts' => [
					'SnakSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Snak',
					'wikibase.datamodel.PropertyValueSnak',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
				],
			],

		'wikibase.serialization.StatementGroupSerializer' => $moduleTemplate + [
				'scripts' => [
					'StatementGroupSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.StatementGroup',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.StatementListSerializer',
				],
			],

		'wikibase.serialization.StatementGroupSetSerializer' => $moduleTemplate + [
				'scripts' => [
					'StatementGroupSetSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.StatementGroupSet',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.StatementGroupSerializer',
				],
			],

		'wikibase.serialization.StatementListSerializer' => $moduleTemplate + [
				'scripts' => [
					'StatementListSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.StatementList',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.StatementSerializer',
				],
			],

		'wikibase.serialization.StatementSerializer' => $moduleTemplate + [
				'scripts' => [
					'StatementSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Statement',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.ClaimSerializer',
					'wikibase.serialization.ReferenceListSerializer',
					'wikibase.serialization.Serializer',
				],
			],

		'wikibase.serialization.TermMapSerializer' => $moduleTemplate + [
				'scripts' => [
					'TermMapSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.TermMap',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.TermSerializer',
				],
			],

		'wikibase.serialization.TermSerializer' => $moduleTemplate + [
				'scripts' => [
					'TermSerializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Term',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
				],
			],

	];

	return $modules;
} );
