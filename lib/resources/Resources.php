<?php

use MediaWiki\MediaWikiServices;
use Wikibase\RepoAccessModule;
use Wikibase\Settings;
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

	$wikibaseApiPaths = [
		'localBasePath' => __DIR__ . '/wikibase-api/src',
		'remoteExtPath' => 'Wikibase/lib/resources/wikibase-api/src',
	];

	$libPaths = [
		'localBasePath' => __DIR__ . '/lib',
		'remoteExtPath' => 'Wikibase/lib/resources/lib',
	];

	$modules = [

		'mw.config.values.wbSiteDetails' => $moduleTemplate + [
			'factory' => function () {
				return new SitesModule(
					Settings::singleton(),
					MediaWikiServices::getInstance()->getSiteStore(),
					MediaWikiServices::getInstance()->getLocalServerObjectCache()
				);
			},
		],

		'mw.config.values.wbRepo' => $moduleTemplate + [
			'class' => RepoAccessModule::class,
		],

		'wikibase' => $moduleTemplate + [
			'scripts' => [
				'wikibase.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
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

		'wikibase.api.RepoApi' => $wikibaseApiPaths + [
			'scripts' => [
				'RepoApi.js',
			],
			'dependencies' => [
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			]
		],

		'wikibase.api.RepoApiError' => $wikibaseApiPaths + [
			'scripts' => [
				'RepoApiError.js',
			],
			'messages' => [
				'wikibase-error-unexpected',
				'wikibase-error-save-generic',
				'wikibase-error-remove-generic',
				'wikibase-error-save-timeout',
				'wikibase-error-remove-timeout',
				'wikibase-error-ui-no-external-page',
				'wikibase-error-ui-edit-conflict',
			],
			'dependencies' => [
				'util.inherit',
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],
		'wikibase.api.__namespace' => $wikibaseApiPaths + [
			'scripts' => [
				'namespace.js'
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'wikibase.api.FormatValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'FormatValueCaller.js',
			],
			'dependencies' => [
				'dataValues.DataValue',
				'wikibase.api.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'wikibase.api.getLocationAgnosticMwApi' => $wikibaseApiPaths + [
			'scripts' => [
				'getLocationAgnosticMwApi.js',
			],
			'dependencies' => [
				'mediawiki.api',
				'mediawiki.ForeignApi',
				'wikibase.api.__namespace',
			],
			'targets' => [
				'desktop',
				'mobile'
			],
		],

		'wikibase.api.ParseValueCaller' => $wikibaseApiPaths + [
			'scripts' => [
				'ParseValueCaller.js',
			],
			'dependencies' => [
				'wikibase.api.__namespace',
				'wikibase.api.RepoApiError',
			]
		],

		'vue2' => $moduleTemplate + [
			'scripts' => 'vendor/vue2.common.prod.js',
			'targets' => [ 'desktop', 'mobile' ],
		],

		'jquery.animateWithEvent' => $libPaths + [
			'scripts' => [
				'jquery/jquery.animateWithEvent.js',
			],
			'dependencies' => [
				'jquery.AnimationEvent',
			],
		],

		'jquery.AnimationEvent' => $libPaths + [
			'scripts' => [
				'jquery/jquery.AnimationEvent.js',
			],
			'dependencies' => [
				'jquery.PurposedCallbacks',
			],
		],

		'jquery.focusAt' => $libPaths + [
			'scripts' => [
				'jquery/jquery.focusAt.js',
			],
		],

		'jquery.inputautoexpand' => $libPaths + [
			'scripts' => [
				'jquery/jquery.inputautoexpand.js',
			],
			'dependencies' => [
				'jquery.event.special.eachchange',
			],
		],

		'jquery.PurposedCallbacks' => $libPaths + [
			'scripts' => [
				'jquery/jquery.PurposedCallbacks.js',
			],
		],

		'jquery.event.special.eachchange' => $libPaths + [
			'scripts' => [
				'jquery.event/jquery.event.special.eachchange.js'
			],
			'dependencies' => [
				'jquery.client',
			],
		],

		'jquery.ui.inputextender' => $libPaths + [
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

		'jquery.ui.listrotator' => $libPaths + [
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

		'jquery.ui.ooMenu' => $libPaths + [
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

		'jquery.ui.preview' => $libPaths + [
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

		'jquery.ui.suggester' => $libPaths + [
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

		'jquery.ui.commonssuggester' => $libPaths + [
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

		'jquery.ui.languagesuggester' => $libPaths + [
			'scripts' => [
				'jquery.ui/jquery.ui.languagesuggester.js',
			],
			'dependencies' => [
				'jquery.ui.suggester',
				'jquery.ui.widget',
			],
		],

		'jquery.ui.toggler' => $libPaths + [
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

		'jquery.ui.unitsuggester' => $libPaths + [
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

		'jquery.util.adaptlettercase' => $libPaths + [
			'scripts' => [
				'jquery.util/jquery.util.adaptlettercase.js',
			],
		],

		'jquery.util.getscrollbarwidth' => $libPaths + [
			'scripts' => [
				'jquery.util/jquery.util.getscrollbarwidth.js',
			],
		],

		'util.ContentLanguages' => $libPaths + [
			'scripts' => [
				'util/util.ContentLanguages.js',
			],
			'dependencies' => [
				'util.inherit',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

		'util.Extendable' => $libPaths + [
			'scripts' => [
				'util/util.Extendable.js',
			],
		],

		'util.highlightSubstring' => $libPaths + [
			'scripts' => [
				'util/util.highlightSubstring.js',
			],
		],

		'util.MessageProvider' => $libPaths + [
			'scripts' => [
				'util/util.MessageProvider.js',
			],
		],
		'util.HashMessageProvider' => $libPaths + [
			'scripts' => [
				'util/util.HashMessageProvider.js',
			],
		],
		'util.CombiningMessageProvider' => $libPaths + [
			'scripts' => [
				'util/util.CombiningMessageProvider.js',
			],
		],
		'util.PrefixingMessageProvider' => $libPaths + [
			'scripts' => [
				'util/util.PrefixingMessageProvider.js',
			],
		],

		'util.Notifier' => $libPaths + [
			'scripts' => [
				'util/util.Notifier.js',
			],
		],

		'util.inherit' => $libPaths + [
			'scripts' => [
				'util/util.inherit.js',
			],
			'targets' => [ 'desktop', 'mobile' ],
		],

	];

	$modules = array_merge(
		$modules,
		require __DIR__ . '/jquery.wikibase/resources.php'
	);

	return $modules;
} );
