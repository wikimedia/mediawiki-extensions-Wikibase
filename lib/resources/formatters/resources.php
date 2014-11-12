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

		'wikibase.formatters.getApiBasedValueFormatterConstructor' => $moduleTemplate + array(
			'scripts' => array(
				'getApiBasedValueFormatterConstructor.js',
			),
			'dependencies' => array(
				'util.inherit',
				'valueFormatters.ValueFormatter',
			),
		),

		'wikibase.formatters.getStore' => $moduleTemplate + array(
			'scripts' => array(
				'getStore.js',
			),
			'dependencies' => array(
				'dataValues.values',
				'valueFormatters.formatters',
				'valueFormatters.ValueFormatterStore',
				'wikibase.api.FormatValueCaller',
				'wikibase.datamodel',
				'wikibase.formatters.getApiBasedValueFormatterConstructor',
			),
		),

	);

} );
