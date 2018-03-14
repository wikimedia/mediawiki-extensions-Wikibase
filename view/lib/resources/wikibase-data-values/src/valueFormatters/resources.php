<?php
/**
 * @license GPL-2.0-or-later
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../../../../wikibase-data-values/src/valueFormatters',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values/src/valueFormatters',
	];

	return [
		'valueFormatters' => $moduleTemplate + [
				'scripts' => [
					'valueFormatters.js',
				],
			],

		'valueFormatters.ValueFormatter' => $moduleTemplate + [
				'scripts' => [
					'formatters/ValueFormatter.js',
				],
				'dependencies' => [
					'util.inherit',
					'valueFormatters',
				],
			],

		'valueFormatters.formatters' => $moduleTemplate + [
				'scripts' => [
					'formatters/NullFormatter.js',
					'formatters/StringFormatter.js',
				],
				'dependencies' => [
					'dataValues.values',
					'util.inherit',
					'valueFormatters.ValueFormatter',
				],
			],
	];
} );
