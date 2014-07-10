<?php
/**
 * @license GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
return call_user_func( function() {

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(

		'wikibase.api.__namespace' => $moduleTemplate + array(
			'scripts' => array(
				'namespace.js'
			),
			'dependencies' => array(
				'wikibase' // For the namespace
			)
		),

		'wikibase.api.FormatValueCaller' => $moduleTemplate + array(
			'scripts' => array(
				'FormatValueCaller.js',
			),
			'dependencies' => array(
				'json',
				'jquery',
				'wikibase.api.__namespace',
			)
		),

		'wikibase.api.ParseValueCaller' => $moduleTemplate + array(
			'scripts' => array(
				'ParseValueCaller.js',
			),
			'dependencies' => array(
				'dataValues',
				'json',
				'jquery',
				'wikibase.api.__namespace',
			)
		),

	);

} );
