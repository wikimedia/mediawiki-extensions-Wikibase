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

	return array(
		'wikibase.parsers.getStore' => array(
			'localBasePath' => __DIR__,
			'remoteExtPath' => '..' . $remoteExtPath[0],
			'scripts' => array(
				'getApiBasedValueParserConstructor.js',
				'getStore.js',
			),
			'dependencies' => array(
				'dataValues',
				'dataValues.values',
				'util.inherit',
				'valueParsers.parsers',
				'valueParsers.ValueParser',
				'valueParsers.ValueParserStore',
				'wikibase',
				'wikibase.api.ParseValueCaller',
				'wikibase.datamodel',
			),
		),
	);
} );
