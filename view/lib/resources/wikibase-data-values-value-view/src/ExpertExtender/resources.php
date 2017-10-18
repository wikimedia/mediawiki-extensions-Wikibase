<?php
/**
 * @license GNU GPL v2+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {

	$dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
		. 'wikibase-data-values-value-view' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'ExpertExtender';

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, $dir, 2
	);
	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => $remoteExtPathParts[1],
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
