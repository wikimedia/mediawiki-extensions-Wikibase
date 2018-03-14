<?php

use Wikibase\RepoAccessModule;
use Wikibase\SitesModule;

/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/lib/resources',
	];

	$modules = [

		'mw.config.values.wbSiteDetails' => $moduleTemplate + [
			'class' => SitesModule::class,
		],

		'mw.config.values.wbRepo' => $moduleTemplate + [
			'class' => RepoAccessModule::class,
		],

		'wikibase' => $moduleTemplate + [
			'scripts' => [
				'wikibase.js',
			],
		],

		'wikibase.buildErrorOutput' => $moduleTemplate + [
			'scripts' => [
				'wikibase.buildErrorOutput.js',
			],
			'dependencies' => [
				'wikibase',
			],
		],

		'wikibase.sites' => $moduleTemplate + [
			'scripts' => [
				'wikibase.sites.js',
			],
			'dependencies' => [
				'mw.config.values.wbSiteDetails',
				'wikibase',
				'wikibase.Site',
			],
		],

	];

	$modules = array_merge(
		$modules,
		include __DIR__ . '/deprecated/resources.php',
		include __DIR__ . '/jquery.wikibase/resources.php'
	);

	return $modules;
} );
