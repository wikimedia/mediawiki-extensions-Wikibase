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
		'wikibase.formatters.getStore' => array(
			'localBasePath' => __DIR__,
			'remoteExtPath' => '..' . $remoteExtPath[0],
			'scripts' => array(
				'getApiBasedValueFormatterConstructor.js',
				'getStore.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'util.inherit',
				'valueFormatters.formatters',
				'valueFormatters.ValueFormatter',
				'valueFormatters.ValueFormatterStore',
				'wikibase',
				'wikibase.api.FormatValueCaller',
				'wikibase.datamodel',
			),
		),
	);

} );
