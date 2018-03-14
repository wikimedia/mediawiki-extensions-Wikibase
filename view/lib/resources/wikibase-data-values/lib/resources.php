<?php

/**
 * @license GPL-2.0-or-later
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../../../wikibase-data-values/lib',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/lib',
	];

	return [
		'globeCoordinate.js' => $moduleTemplate + [
				'scripts' => [
					'globeCoordinate/globeCoordinate.js',
					'globeCoordinate/globeCoordinate.GlobeCoordinate.js',
				],
			],

		'util.inherit' => $moduleTemplate + [
				'scripts' => [
					'util/util.inherit.js',
				],
			],
	];
} );
