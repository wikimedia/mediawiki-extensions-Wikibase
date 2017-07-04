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
	$moduleBase = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	return [
		'jquery.wikibase.siteselector.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.siteselector.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.siteselector',
				'wikibase.Site',
			],
		],

		'jquery.wikibase.wbtooltip.tests' => $moduleBase + [
			'scripts' => [
				'jquery.wikibase.wbtooltip.tests.js',
			],
			'dependencies' => [
				'jquery.wikibase.wbtooltip',
			],
		],
	];
} );
