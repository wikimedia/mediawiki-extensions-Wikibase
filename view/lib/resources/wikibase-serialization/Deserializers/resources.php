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
		. DIRECTORY_SEPARATOR . 'Deserializers';

	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', $dir, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	$modules = [
		'wikibase.serialization.ClaimDeserializer' => $moduleTemplate + [
				'scripts' => [
					'ClaimDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Claim',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.SnakListDeserializer',
					'wikibase.serialization.SnakDeserializer',
				],
			],

		'wikibase.serialization.Deserializer' => $moduleTemplate + [
				'scripts' => [
					'Deserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.serialization.__namespace',
				],
			],

		'wikibase.serialization.EntityDeserializer' => $moduleTemplate + [
				'scripts' => [
					'EntityDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Item',
					'wikibase.datamodel.Property',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.ItemDeserializer',
					'wikibase.serialization.PropertyDeserializer',
					'wikibase.serialization.StrategyProvider',
				],
			],

		'wikibase.serialization.FingerprintDeserializer' => $moduleTemplate + [
				'scripts' => [
					'FingerprintDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Fingerprint',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.MultiTermMapDeserializer',
					'wikibase.serialization.TermMapDeserializer',
				],
			],

		'wikibase.serialization.ItemDeserializer' => $moduleTemplate + [
				'scripts' => [
					'ItemDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Item',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.FingerprintDeserializer',
					'wikibase.serialization.SiteLinkSetDeserializer',
					'wikibase.serialization.StatementGroupSetDeserializer',
				],
			],

		'wikibase.serialization.MultiTermDeserializer' => $moduleTemplate + [
				'scripts' => [
					'MultiTermDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.MultiTerm',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
				],
			],

		'wikibase.serialization.MultiTermMapDeserializer' => $moduleTemplate + [
				'scripts' => [
					'MultiTermMapDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.MultiTermMap',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.MultiTermDeserializer',
				],
			],

		'wikibase.serialization.PropertyDeserializer' => $moduleTemplate + [
				'scripts' => [
					'PropertyDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Property',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.FingerprintDeserializer',
					'wikibase.serialization.StatementGroupSetDeserializer',
				],
			],

		'wikibase.serialization.ReferenceListDeserializer' => $moduleTemplate + [
				'scripts' => [
					'ReferenceListDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.ReferenceList',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.ReferenceDeserializer',
				],
			],

		'wikibase.serialization.ReferenceDeserializer' => $moduleTemplate + [
				'scripts' => [
					'ReferenceDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Reference',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.SnakListDeserializer',
				],
			],

		'wikibase.serialization.SiteLinkSetDeserializer' => $moduleTemplate + [
				'scripts' => [
					'SiteLinkSetDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.SiteLinkSet',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.SiteLinkDeserializer',
				],
			],

		'wikibase.serialization.SiteLinkDeserializer' => $moduleTemplate + [
				'scripts' => [
					'SiteLinkDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.SiteLink',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
				],
			],

		'wikibase.serialization.SnakListDeserializer' => $moduleTemplate + [
				'scripts' => [
					'SnakListDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.SnakList',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.SnakDeserializer',
				],
			],

		'wikibase.serialization.SnakDeserializer' => $moduleTemplate + [
				'scripts' => [
					'SnakDeserializer.js',
				],
				'dependencies' => [
					'dataValues',
					'dataValues.values',
					'util.inherit',
					'wikibase.datamodel.PropertyNoValueSnak',
					'wikibase.datamodel.PropertySomeValueSnak',
					'wikibase.datamodel.PropertyValueSnak',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
				],
			],

		'wikibase.serialization.StatementGroupSetDeserializer' => $moduleTemplate + [
				'scripts' => [
					'StatementGroupSetDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.StatementGroupSet',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.StatementGroupDeserializer',
				],
			],

		'wikibase.serialization.StatementGroupDeserializer' => $moduleTemplate + [
				'scripts' => [
					'StatementGroupDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.StatementGroup',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.StatementListDeserializer',
				],
			],

		'wikibase.serialization.StatementListDeserializer' => $moduleTemplate + [
				'scripts' => [
					'StatementListDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.StatementList',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.StatementDeserializer',
				],
			],

		'wikibase.serialization.StatementDeserializer' => $moduleTemplate + [
				'scripts' => [
					'StatementDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Statement',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.ClaimDeserializer',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.ReferenceListDeserializer',
				],
			],

		'wikibase.serialization.TermDeserializer' => $moduleTemplate + [
				'scripts' => [
					'TermDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.Term',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
				],
			],

		'wikibase.serialization.TermMapDeserializer' => $moduleTemplate + [
				'scripts' => [
					'TermMapDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.TermMap',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.TermDeserializer',
				],
			],

	];

	return $modules;
} );
