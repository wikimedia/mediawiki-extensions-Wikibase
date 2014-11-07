<?php
/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	preg_match(
		'+' . preg_quote( DIRECTORY_SEPARATOR, '+' ) . '((?:vendor|extensions)' .
			preg_quote( DIRECTORY_SEPARATOR, '+' ) . '.*)$+',
		__DIR__,
		$remoteExtPathParts
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . DIRECTORY_SEPARATOR . $remoteExtPathParts[1],
	);

	return array(

		'wikibase.parsers.getApiBasedValueParserConstructor' => $moduleTemplate + array(
			'scripts' => array(
				'getApiBasedValueParserConstructor.js',
			),
			'dependencies' => array(
				'dataValues',
				'util.inherit',
				'valueParsers.ValueParser',
				'wikibase',
			),
		),

		'wikibase.parsers.getStore' => $moduleTemplate + array(
			'scripts' => array(
				'getStore.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'valueParsers.parsers',
				'valueParsers.ValueParserStore',
				'wikibase.api.ParseValueCaller',
				'wikibase.parsers.getApiBasedValueParserConstructor',
				'wikibase.datamodel',
			),
		),

	);

} );
