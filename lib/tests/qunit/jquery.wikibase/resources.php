<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, __DIR__, 2
	);
	$moduleBase = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	);

	return array(
		'jquery.wikibase.siteselector.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.siteselector.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.siteselector',
				'wikibase.Site',
			),
		),

		'jquery.wikibase.wbtooltip.tests' => $moduleBase + array(
			'scripts' => array(
				'jquery.wikibase.wbtooltip.tests.js',
			),
			'dependencies' => array(
				'jquery.wikibase.wbtooltip',
			),
		),
	);
} );
