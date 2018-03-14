<?php

/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/lib/resources/jquery.wikibase',
	];

	return [
		'jquery.wikibase.siteselector' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.siteselector.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
				'jquery.ui.ooMenu',
				'jquery.ui.suggester',
				'util.highlightSubstring',
			],
		],

		'jquery.wikibase.wbtooltip' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase.wbtooltip.js',
			],
			'styles' => [
				'themes/default/jquery.wikibase.wbtooltip.css'
			],
			'dependencies' => [
				'jquery.tipsy',
				'jquery.ui.widget',
				'wikibase.buildErrorOutput',
			],
		],
	];
} );
