<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Thiemo Mättig
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	return array(
		'jquery.wikibase.snakview' => array(
			'localBasePath' => __DIR__,
			'remoteExtPath' => '..' . $remoteExtPath[0],
			'scripts' => array(
				'snakview.js',
				'snakview.SnakTypeSelector.js',
				'snakview.variations.js',
				'snakview.variations.NoValue.js',
				'snakview.variations.SomeValue.js',
				'snakview.variations.Value.js',
				'snakview.variations.Variation.js',
				'snakview.ViewState.js',
			),
			'styles' => array(
				'themes/default/snakview.SnakTypeSelector.css',
			),
			'dependencies' => array(
				'dataValues',
				'dataValues.DataValue',
				'jquery.event.special.eachchange',
				'jquery.ui.position',
				'jquery.ui.TemplatedWidget',
				'jquery.wikibase.entityselector',
				'mediawiki.legacy.shared',
				'mw.config.values.wbRepo',
				'util.inherit',
				'wikibase.datamodel',
				'wikibase.serialization.SnakDeserializer',
				'wikibase.serialization.SnakSerializer',
			),
			'messages' => array(
				'wikibase-snakview-choosesnaktype',
				'wikibase-snakview-property-input-placeholder',
				'wikibase-snakview-snaktypeselector-novalue',
				'wikibase-snakview-snaktypeselector-somevalue',
				'wikibase-snakview-snaktypeselector-value',
				'wikibase-snakview-variation-datavaluetypemismatch',
				'wikibase-snakview-variation-datavaluetypemismatch-details',
				'wikibase-snakview-variation-nonewvaluefordeletedproperty',
				'wikibase-snakview-variations-novalue-label',
				'wikibase-snakview-variations-somevalue-label',
			),
		),
	);
} );
