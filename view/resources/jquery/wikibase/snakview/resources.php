<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Kreuz
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
		'jquery.wikibase.snakview' => $moduleTemplate + [
			'scripts' => [
				'snakview.js',
				'snakview.SnakTypeSelector.js',
			],
			'styles' => [
				'themes/default/snakview.SnakTypeSelector.css',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'jquery.event.special.eachchange',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.position',
				'jquery.wikibase.entityselector',
				'jquery.wikibase.snakview.variations',
				'jquery.wikibase.snakview.ViewState',
				'mediawiki.legacy.shared',
				'mw.config.values.wbRepo',
				'wikibase.datamodel',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
			],
			'messages' => [
				'wikibase-snakview-property-input-placeholder',
				'wikibase-snakview-choosesnaktype',
				'wikibase-snakview-snaktypeselector-value',
				'wikibase-snakview-snaktypeselector-somevalue',
				'wikibase-snakview-snaktypeselector-novalue'
			],
		],

		'jquery.wikibase.snakview.variations' => $moduleTemplate + [
			'scripts' => [
				'snakview.variations.js',
				'snakview.variations.Variation.js',
				'snakview.variations.NoValue.js',
				'snakview.variations.SomeValue.js',
				'snakview.variations.Value.js',
			],
			'dependencies' => [
				'dataValues',
				'util.inherit',
				'wikibase.datamodel',
			],
			'messages' => [
				'wikibase-snakview-variation-datavaluetypemismatch',
				'wikibase-snakview-variation-datavaluetypemismatch-details',
				'wikibase-snakview-variation-nonewvaluefordeletedproperty',
				'wikibase-snakview-variations-novalue-label',
				'wikibase-snakview-variations-somevalue-label',
			],
		],

		'jquery.wikibase.snakview.ViewState' => $moduleTemplate + [
			'scripts' => [
				'snakview.ViewState.js',
			],
		],
	];
} );
