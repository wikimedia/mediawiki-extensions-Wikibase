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

	return [

		'wikibase.experts.__namespace' => $moduleTemplate + [
			'scripts' => [
				'__namespace.js'
			],
			'dependencies' => [
				'wikibase',
			]
		],

		'wikibase.experts.getStore' => $moduleTemplate + [
			'scripts' => [
				'getStore.js',
			],
			'dependencies' => [
				'dataValues.values',
				'jquery.valueview.ExpertStore',
				'jquery.valueview.experts.CommonsMediaType',
				'jquery.valueview.experts.GlobeCoordinateInput',
				'jquery.valueview.experts.MonolingualText',
				'jquery.valueview.experts.QuantityInput',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.experts.TimeInput',
				'jquery.valueview.experts.UnDeserializableValue',
				'jquery.valueview.experts.UnsupportedValue',
				'wikibase.datamodel.EntityId',
				'wikibase.experts.__namespace',
				'wikibase.experts.Item',
				'wikibase.experts.Property',
			],
		],

		'wikibase.experts.Entity' => $moduleTemplate + [
			'scripts' => [
				'Entity.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.valueview.Expert',
				'jquery.valueview.experts.StringValue',
				'jquery.wikibase.entityselector',
				'mw.config.values.wbRepo',
				'util.inherit',
				'wikibase.experts.__namespace',
			],
		],

		'wikibase.experts.Item' => $moduleTemplate + [
			'scripts' => [
				'Item.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
				'wikibase.experts.__namespace',
				'wikibase.experts.Entity',
			],
		],

		'wikibase.experts.Property' => $moduleTemplate + [
			'scripts' => [
				'Property.js',
			],
			'dependencies' => [
				'jquery.valueview.Expert',
				'wikibase.experts.__namespace',
				'wikibase.experts.Entity',
			],
		],
	];

} );
