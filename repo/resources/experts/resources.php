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
		'remoteExtPath' => 'Wikibase/repo/resources/experts',
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
				'wikibase.experts.__namespace',
				'wikibase.experts.modules',
				'dataValues.values',
				'jquery.valueview.ExpertStore',
				'jquery.valueview.experts.StringValue',
				'jquery.valueview.experts.UnDeserializableValue',
				'jquery.valueview.experts.UnsupportedValue',
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
