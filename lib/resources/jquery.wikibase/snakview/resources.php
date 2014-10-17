<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	$modules = array(

		'jquery.wikibase.snakview' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.js',
				'snakview.SnakTypeSelector.js',
			),
			'styles' => array(
				'themes/default/snakview.SnakTypeSelector.css',
			),
			'dependencies' => array(
				'jquery.event.special.eachchange',
				'jquery.NativeEventHandler',
				'jquery.ui.position',
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.entityselector',
				'jquery.wikibase.snakview.variations',
				'jquery.wikibase.snakview.variations.NoValue',
				'jquery.wikibase.snakview.variations.SomeValue',
				'jquery.wikibase.snakview.variations.Value',
				'jquery.wikibase.snakview.ViewState',
				'mediawiki.legacy.shared',
				'mw.config.values.wbRepo',
				'wikibase.datamodel',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
				'wikibase.utilities',
			),
			'messages' => array(
				'wikibase-snakview-property-input-placeholder',
				'wikibase-snakview-choosesnaktype',
				'wikibase-snakview-snaktypeselector-value',
				'wikibase-snakview-snaktypeselector-somevalue',
				'wikibase-snakview-snaktypeselector-novalue'
			),
		),

		'jquery.wikibase.snakview.variations' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.variations.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snakview.variations.Variation',
				'util.inherit',
			),
		),

		'jquery.wikibase.snakview.variations.Variation' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.variations.Variation.js',
			),
			'dependencies' => array(
				'util.inherit',
			),
		),

		'jquery.wikibase.snakview.variations.NoValue' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.variations.NoValue.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snakview.variations',
				'jquery.wikibase.snakview.variations.Variation',
				'wikibase.datamodel',
			),
			'messages' => array(
				'wikibase-snakview-variations-novalue-label',
			),
		),

		'jquery.wikibase.snakview.variations.SomeValue' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.variations.SomeValue.js',
			),
			'dependencies' => array(
				'jquery.wikibase.snakview.variations',
				'jquery.wikibase.snakview.variations.Variation',
				'wikibase.datamodel',
			),
			'messages' => array(
				'wikibase-snakview-variations-somevalue-label',
			),
		),

		'jquery.wikibase.snakview.variations.Value' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.variations.Value.js',
			),
			'dependencies' => array(
				'dataValues',
				'jquery.wikibase.snakview.variations',
				'jquery.wikibase.snakview.variations.Variation',
				'wikibase.datamodel',
				'wikibase.dataTypes',
			),
			'messages' => array(
				'wikibase-snakview-variation-datavaluetypemismatch',
				'wikibase-snakview-variation-datavaluetypemismatch-details',
				'wikibase-snakview-variation-nonewvaluefordeletedproperty',
			),
		),

		'jquery.wikibase.snakview.ViewState' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.ViewState.js',
			),
		),

	);

	return $modules;
} );
