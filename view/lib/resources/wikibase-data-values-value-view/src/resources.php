<?php
/**
 * @license GNU GPL v2+
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author H. Snater < mediawiki@snater.com >
 *
 * @codeCoverageIgnoreStart
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../../../wikibase-data-values-value-view/src',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view/src',
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
