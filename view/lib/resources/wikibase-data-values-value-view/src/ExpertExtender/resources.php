<?php
/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../../../../wikibase-data-values-value-view/src/ExpertExtender',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-data-values-value-view/src/ExpertExtender',
	];

	$modules = [
		'jquery.valueview.ExpertExtender' => $moduleTemplate + [
				'scripts' => [
					'ExpertExtender.js',
				],
				'dependencies' => [
					'jquery.ui.inputextender',
					'jquery.valueview',
					'util.Extendable',
				],
			],

		'jquery.valueview.ExpertExtender.Container' => $moduleTemplate + [
				'scripts' => [
					'ExpertExtender.Container.js',
				],
				'dependencies' => [
					'jquery.valueview.ExpertExtender',
				],
			],

		'jquery.valueview.ExpertExtender.LanguageSelector' => $moduleTemplate + [
				'scripts' => [
					'ExpertExtender.LanguageSelector.js',
				],
				'dependencies' => [
					'jquery.event.special.eachchange',
					'jquery.ui.languagesuggester',
					'jquery.valueview.ExpertExtender',
					'util.PrefixingMessageProvider',
				],
				'messages' => [
					'valueview-expertextender-languageselector-languagetemplate',
					'valueview-expertextender-languageselector-label',
				]
			],

		'jquery.valueview.ExpertExtender.Listrotator' => $moduleTemplate + [
				'scripts' => [
					'ExpertExtender.Listrotator.js',
				],
				'dependencies' => [
					'jquery.ui.listrotator',
					'jquery.valueview.ExpertExtender',
				],
			],

		'jquery.valueview.ExpertExtender.Preview' => $moduleTemplate + [
				'scripts' => [
					'ExpertExtender.Preview.js',
				],
				'styles' => [
					'ExpertExtender.Preview.css',
				],
				'dependencies' => [
					'jquery.ui.preview',
					'jquery.valueview.ExpertExtender',
					'util.PrefixingMessageProvider',
				],
				'messages' => [
					'valueview-preview-label',
					'valueview-preview-novalue',
				],
			],

		'jquery.valueview.ExpertExtender.UnitSelector' => $moduleTemplate + [
				'scripts' => [
					'ExpertExtender.UnitSelector.js',
				],
				'dependencies' => [
					'jquery.valueview.ExpertExtender',
					'jquery.ui.unitsuggester',
				],
				'messages' => [
					'valueview-expertextender-unitsuggester-label',
				],
			],
	];

	return $modules;
} );
