<?php
/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Mättig
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	);

	return array(
		'jquery.wikibase.snakview' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.js',
				'snakview.SnakTypeSelector.js',
			),
			'styles' => array(
				'themes/default/snakview.SnakTypeSelector.css',
			),
			'dependencies' => array(
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
				'snakview.variations.Variation.js',
				'snakview.variations.NoValue.js',
				'snakview.variations.SomeValue.js',
				'snakview.variations.Value.js',
			),
			'dependencies' => array(
				'dataValues',
				'util.inherit',
				'wikibase.datamodel',
			),
			'messages' => array(
				'wikibase-snakview-variation-datavaluetypemismatch',
				'wikibase-snakview-variation-datavaluetypemismatch-details',
				'wikibase-snakview-variation-nonewvaluefordeletedproperty',
				'wikibase-snakview-variations-novalue-label',
				'wikibase-snakview-variations-somevalue-label',
			),
		),

		'jquery.wikibase.snakview.ViewState' => $moduleTemplate + array(
			'scripts' => array(
				'snakview.ViewState.js',
			),
		),
	);
} );
