<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Kreuz
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/view/resources/jquery/wikibase/snakview',
	];

	return [
		'jquery.wikibase.snakview' => $moduleTemplate + [
			'scripts' => [
				'snakview.ViewState.js',
				'snakview.variations.js',
				'snakview.variations.Variation.js',
				'snakview.variations.NoValue.js',
				'snakview.variations.SomeValue.js',
				'snakview.variations.Value.js',
				'snakview.js',
				'snakview.SnakTypeSelector.js',
			],
			'styles' => [
				'themes/default/snakview.SnakTypeSelector.css',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'dataValues',
				'jquery.event.special.eachchange',
				'jquery.ui.EditableTemplatedWidget',
				'jquery.ui.position',
				'jquery.wikibase.entityselector',
				'mediawiki.legacy.shared',
				'mw.config.values.wbRepo',
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
			],
			'messages' => [
				'wikibase-snakview-property-input-placeholder',
				'wikibase-snakview-choosesnaktype',
				'wikibase-snakview-snaktypeselector-value',
				'wikibase-snakview-snaktypeselector-somevalue',
				'wikibase-snakview-snaktypeselector-novalue',
				'wikibase-snakview-variation-datavaluetypemismatch',
				'wikibase-snakview-variation-datavaluetypemismatch-details',
				'wikibase-snakview-variation-nonewvaluefordeletedproperty',
				'wikibase-snakview-variations-novalue-label',
				'wikibase-snakview-variations-somevalue-label',
			],
		],
	];
} );
