<?php
use Wikibase\View\Termbox\TermboxDependencyLoader;

/**
 * @license GPL-2.0-or-later
 */

return call_user_func( function () {
	$moduleTemplate = [
		'localBasePath' => __DIR__ . '/../wikibase-termbox',
		'remoteExtPath' => 'Wikibase/view/lib/wikibase-termbox',
	];

	return [
		'wikibase.termbox' => $moduleTemplate + [
			'scripts' => [
				'dist/wikibase.termbox.main.js',
			],
			'targets' => 'mobile',
			'dependencies' => [
				'wikibase.termbox.styles',
				'wikibase.getLanguageNameByCode',
				'wikibase.entityPage.entityLoaded',
				'wikibase.WikibaseContentLanguages',
			],
			'messages' => TermboxDependencyLoader::getMessages( $moduleTemplate[ 'localBasePath' ] . '/dist/dependency.json' )
		],

		'wikibase.termbox.styles' => $moduleTemplate + [
			'styles' => [
				'../../resources/wikibase/termbox/main.less',
				'dist/wikibase.termbox.main.css',
			],
			'targets' => 'mobile'
		]
	];
} );
