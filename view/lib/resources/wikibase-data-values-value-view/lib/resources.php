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
		. '..' . DIRECTORY_SEPARATOR . 'wikibase-data-values-value-view' . DIRECTORY_SEPARATOR . 'lib';

	$remoteExtPathParts = explode(
		DIRECTORY_SEPARATOR . 'extensions' . DIRECTORY_SEPARATOR, $dir, 2
	);
	$moduleTemplate = [
		'localBasePath' => $dir,
		'remoteExtPath' => $remoteExtPathParts[1],
	];

	return [

		'jquery.animateWithEvent' => $moduleTemplate + [
				'scripts' => [
					'jquery/jquery.animateWithEvent.js',
				],
				'dependencies' => [
					'jquery.AnimationEvent',
				],
			],

		'jquery.AnimationEvent' => $moduleTemplate + [
				'scripts' => [
					'jquery/jquery.AnimationEvent.js',
				],
				'dependencies' => [
					'jquery.PurposedCallbacks',
				],
			],

		'jquery.autocompletestring' => $moduleTemplate + [
				'scripts' => [
					'jquery/jquery.autocompletestring.js',
				],
				'dependencies' => [
					'jquery.util.adaptlettercase',
				],
			],

		'jquery.focusAt' => $moduleTemplate + [
				'scripts' => [
					'jquery/jquery.focusAt.js',
				],
			],

		'jquery.inputautoexpand' => $moduleTemplate + [
				'scripts' => [
					'jquery/jquery.inputautoexpand.js',
				],
				'dependencies' => [
					'jquery.event.special.eachchange',
				],
			],

		'jquery.PurposedCallbacks' => $moduleTemplate + [
				'scripts' => [
					'jquery/jquery.PurposedCallbacks.js',
				],
			],

		'jquery.event.special.eachchange' => $moduleTemplate + [
				'scripts' => [
					'jquery.event/jquery.event.special.eachchange.js'
				],
				'dependencies' => [
					'jquery.client',
				],
			],

		'jquery.ui.inputextender' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.inputextender.js',
				],
				'styles' => [
					'jquery.ui/jquery.ui.inputextender.css',
				],
				'dependencies' => [
					'jquery.animateWithEvent',
					'jquery.event.special.eachchange',
					'jquery.ui.position',
					'jquery.ui.widget',
				],
			],

		'jquery.ui.listrotator' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.listrotator.js',
				],
				'styles' => [
					'jquery.ui/jquery.ui.listrotator.css',
				],
				'dependencies' => [
					'jquery.ui.autocomplete', // needs jquery.ui.menu
					'jquery.ui.widget',
					'jquery.ui.position',
				],
				'messages' => [
					'valueview-listrotator-manually',
				],
			],

		'jquery.ui.ooMenu' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.ooMenu.js',
				],
				'styles' => [
					'jquery.ui/jquery.ui.ooMenu.css',
				],
				'dependencies' => [
					'jquery.ui.widget',
					'jquery.util.getscrollbarwidth',
					'util.inherit',
				],
			],

		'jquery.ui.preview' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.preview.js',
				],
				'styles' => [
					'jquery.ui/jquery.ui.preview.css',
				],
				'dependencies' => [
					'jquery.ui.widget',
					'util.CombiningMessageProvider',
					'util.HashMessageProvider'
				],
			],

		'jquery.ui.suggester' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.suggester.js',
				],
				'styles' => [
					'jquery.ui/jquery.ui.suggester.css',
				],
				'dependencies' => [
					'jquery.ui.core',
					'jquery.ui.ooMenu',
					'jquery.ui.position',
					'jquery.ui.widget',
				],
			],

		'jquery.ui.commonssuggester' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.commonssuggester.js',
				],
				'styles' => [
					'jquery.ui/jquery.ui.commonssuggester.css',
				],
				'dependencies' => [
					'jquery.ui.suggester',
					'jquery.ui.widget',
					'util.highlightSubstring',
				],
			],

		'jquery.ui.languagesuggester' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.languagesuggester.js',
				],
				'dependencies' => [
					'jquery.ui.suggester',
					'jquery.ui.widget',
				],
			],

		'jquery.ui.toggler' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.toggler.js',
				],
				'styles' => [
					'jquery.ui/jquery.ui.toggler.css',
				],
				'dependencies' => [
					'jquery.animateWithEvent',
					'jquery.ui.core',
					'jquery.ui.widget',
				],
			],

		'jquery.ui.unitsuggester' => $moduleTemplate + [
				'scripts' => [
					'jquery.ui/jquery.ui.unitsuggester.js',
				],
				'styles' => [
					'jquery.ui/jquery.ui.unitsuggester.css',
				],
				'dependencies' => [
					'jquery.ui.suggester',
					'jquery.ui.widget',
				],
			],

		'jquery.util.adaptlettercase' => $moduleTemplate + [
				'scripts' => [
					'jquery.util/jquery.util.adaptlettercase.js',
				],
			],

		'jquery.util.getscrollbarwidth' => $moduleTemplate + [
				'scripts' => [
					'jquery.util/jquery.util.getscrollbarwidth.js',
				],
			],

		'util.ContentLanguages' => $moduleTemplate + [
				'scripts' => [
					'util/util.ContentLanguages.js',
				],
				'dependencies' => [
					'util.inherit',
				]
			],

		'util.Extendable' => $moduleTemplate + [
				'scripts' => [
					'util/util.Extendable.js',
				],
			],

		'util.highlightSubstring' => $moduleTemplate + [
				'scripts' => [
					'util/util.highlightSubstring.js',
				],
			],

		'util.MessageProvider' => $moduleTemplate + [
				'scripts' => [
					'util/util.MessageProvider.js',
				],
			],
		'util.HashMessageProvider' => $moduleTemplate + [
				'scripts' => [
					'util/util.HashMessageProvider.js',
				],
			],
		'util.CombiningMessageProvider' => $moduleTemplate + [
				'scripts' => [
					'util/util.CombiningMessageProvider.js',
				],
			],
		'util.PrefixingMessageProvider' => $moduleTemplate + [
				'scripts' => [
					'util/util.PrefixingMessageProvider.js',
				],
			],

		'util.Notifier' => $moduleTemplate + [
				'scripts' => [
					'util/util.Notifier.js',
				],
			],

	];
} );
