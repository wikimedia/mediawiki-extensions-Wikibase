<?php

/**
 * @license GPL-2.0+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
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
