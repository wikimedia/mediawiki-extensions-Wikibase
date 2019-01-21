<?php

/**
 * @license GPL-2.0-or-later
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../wikibase-serialization/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-serialization/src',
	];

	$modules = [

		'wikibase.serialization' => $moduleTemplate + [
				'dependencies' => [
					'wikibase.serialization.__namespace',
					'wikibase.serialization.DeserializerFactory',
					'wikibase.serialization.SerializerFactory',
				],
			],

		'wikibase.serialization.__namespace' => $moduleTemplate + [
				'scripts' => [
					'__namespace.js',
				],
				'dependencies' => [
					'wikibase',
				],
			],

		'wikibase.serialization.DeserializerFactory' => $moduleTemplate + [
				'scripts' => [
					'DeserializerFactory.js',
				],
				'dependencies' => [
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.StrategyProvider',

					'wikibase.serialization.ClaimDeserializer',
					'wikibase.serialization.EntityDeserializer',
					'wikibase.serialization.FingerprintDeserializer',
					'wikibase.serialization.MultiTermDeserializer',
					'wikibase.serialization.MultiTermMapDeserializer',
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
					'wikibase.serialization.TermMapDeserializer',
				],
			],

		'wikibase.serialization.SerializerFactory' => $moduleTemplate + [
				'scripts' => [
					'SerializerFactory.js',
				],
				'dependencies' => [
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Serializer',
					'wikibase.serialization.StrategyProvider',

					'wikibase.serialization.ClaimSerializer',
					'wikibase.serialization.EntitySerializer',
					'wikibase.serialization.FingerprintSerializer',
					'wikibase.serialization.MultiTermMapSerializer',
					'wikibase.serialization.MultiTermSerializer',
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
					'wikibase.serialization.TermMapSerializer',
					'wikibase.serialization.TermSerializer',
				],
			],

		'wikibase.serialization.StrategyProvider' => $moduleTemplate + [
				'scripts' => [
					'StrategyProvider.js',
				],
				'dependencies' => [
					'wikibase.serialization.__namespace',
				],
			],

		'wikibase.serialization.ClaimDeserializer' => $moduleTemplate + [
				'scripts' => [
					'Deserializers/ClaimDeserializer.js',
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
					'Deserializers/Deserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.serialization.__namespace',
				],
			],

		'wikibase.serialization.EntityDeserializer' => $moduleTemplate + [
				'scripts' => [
					'Deserializers/EntityDeserializer.js',
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
					'Deserializers/FingerprintDeserializer.js',
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
					'Deserializers/ItemDeserializer.js',
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
					'Deserializers/MultiTermDeserializer.js',
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
					'Deserializers/MultiTermMapDeserializer.js',
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
					'Deserializers/PropertyDeserializer.js',
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
					'Deserializers/ReferenceListDeserializer.js',
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
					'Deserializers/ReferenceDeserializer.js',
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
					'Deserializers/SiteLinkSetDeserializer.js',
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
					'Deserializers/SiteLinkDeserializer.js',
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
					'Deserializers/SnakListDeserializer.js',
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
					'Deserializers/SnakDeserializer.js',
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
					'Deserializers/StatementGroupSetDeserializer.js',
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
					'Deserializers/StatementGroupDeserializer.js',
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
					'Deserializers/StatementListDeserializer.js',
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
					'Deserializers/StatementDeserializer.js',
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
					'Deserializers/TermDeserializer.js',
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
					'Deserializers/TermMapDeserializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.datamodel.TermMap',
					'wikibase.serialization.__namespace',
					'wikibase.serialization.Deserializer',
					'wikibase.serialization.TermDeserializer',
				],
			],

		'wikibase.serialization.ClaimSerializer' => $moduleTemplate + [
				'scripts' => [
					'Serializers/ClaimSerializer.js',
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
					'Serializers/EntitySerializer.js',
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
					'Serializers/FingerprintSerializer.js',
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
					'Serializers/ItemSerializer.js',
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
					'Serializers/MultiTermMapSerializer.js',
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
					'Serializers/MultiTermSerializer.js',
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
					'Serializers/PropertySerializer.js',
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
					'Serializers/ReferenceListSerializer.js',
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
					'Serializers/ReferenceSerializer.js',
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
					'Serializers/Serializer.js',
				],
				'dependencies' => [
					'util.inherit',
					'wikibase.serialization.__namespace',
				],
			],

		'wikibase.serialization.SiteLinkSerializer' => $moduleTemplate + [
				'scripts' => [
					'Serializers/SiteLinkSerializer.js',
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
					'Serializers/SiteLinkSetSerializer.js',
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
					'Serializers/SnakListSerializer.js',
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
					'Serializers/SnakSerializer.js',
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
					'Serializers/StatementGroupSerializer.js',
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
					'Serializers/StatementGroupSetSerializer.js',
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
					'Serializers/StatementListSerializer.js',
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
					'Serializers/StatementSerializer.js',
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
					'Serializers/TermMapSerializer.js',
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
					'Serializers/TermSerializer.js',
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
