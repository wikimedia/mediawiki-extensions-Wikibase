<?php
/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {

	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . 'wikibase-data-values-value-view' . DIRECTORY_SEPARATOR . 'src';

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, $dir, 2
	);
	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	$resources = [

		// Loads the actual valueview widget into jQuery.valueview.valueview and maps
		// jQuery.valueview to jQuery.valueview.valueview without losing any properties.
		'jquery.valueview' => $moduleTemplate + [
				'scripts' => [
					'jquery.valueview.js',
				],
				'dependencies' => [
					'jquery.valueview.valueview',
				],
			],

		'jquery.valueview.Expert' => $moduleTemplate + [
				'scripts' => [
					'jquery.valueview.Expert.js',
				],
				'dependencies' => [
					'util.inherit',
					'util.CombiningMessageProvider',
					'util.HashMessageProvider',
					'util.Notifier',
					'util.Extendable'
				],
			],

		'jquery.valueview.ExpertStore' => $moduleTemplate + [
				'scripts' => [
					'jquery.valueview.ExpertStore.js',
				],
			],

		'jquery.valueview.experts' => $moduleTemplate + [
				'scripts' => [
					'jquery.valueview.experts.js',
				],
			],

		// The actual valueview widget:
		'jquery.valueview.valueview' => $moduleTemplate + [
				'scripts' => [
					'jquery.valueview.valueview.js',
				],
				'styles' => [
					'jquery.valueview.valueview.css',
				],
				'dependencies' => [
					'dataValues.DataValue',
					'jquery.ui.widget',
					'jquery.valueview.ViewState',
					'jquery.valueview.ExpertStore',
					'jquery.valueview.experts.EmptyValue',
					'jquery.valueview.experts.UnsupportedValue',
					'util.Notifier',
					'valueFormatters.ValueFormatter',
					'valueParsers.ValueParserStore',
				],
			],

		'jquery.valueview.ViewState' => $moduleTemplate + [
				'scripts' => [
					'jquery.valueview.ViewState.js',
				],
			],

	];

	return array_merge(
		$resources,
		include __DIR__ . '/experts/resources.php',
		include __DIR__ . '/ExpertExtender/resources.php'
	);
} );
