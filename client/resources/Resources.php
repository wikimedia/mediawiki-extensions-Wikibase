<?php

use Wikibase\Client\Modules\SiteModule;

return call_user_func( function() {
	preg_match( '+' . preg_quote( DIRECTORY_SEPARATOR ) . '(?:vendor|extensions)'
		. preg_quote( DIRECTORY_SEPARATOR ) . '.*+', __DIR__, $remoteExtPath );

	$moduleTemplate = [
		'localBasePath' => __DIR__,
		'remoteExtPath' => '..' . $remoteExtPath[0],
	];

	return [
		'wikibase.client.getMwApiForRepo' => $moduleTemplate + [
			'scripts' => [
				'wikibase.client.getMwApiForRepo.js'
			],
			'dependencies' => [
				'mw.config.values.wbRepo',
				'wikibase.api.getLocationAgnosticMwApi',
			]
		],

		'wikibase.client.init' => $moduleTemplate + [
			'position' => 'top',
			'skinStyles' => [
				'modern' => 'wikibase.client.css',
				'monobook' => 'wikibase.client.css',
				'vector' => [
					'wikibase.client.css',
					'wikibase.client.vector.css'
				]
			],
		],

		'wikibase.client.currentSite' => $moduleTemplate + [
			'class' => SiteModule::class
		],

		'wikibase.client.page-move' => $moduleTemplate + [
			'position' => 'top',
			'styles' => 'wikibase.client.page-move.css'
		],

		'wikibase.client.changeslist.css' => $moduleTemplate + [
			'position' => 'top',
			'styles' => 'wikibase.client.changeslist.css'
		],

		'wikibase.client.linkitem.init' => $moduleTemplate + [
			'scripts' => [
				'wikibase.client.linkitem.init.js'
			],
			'messages' => [
				'unknown-error'
			],
			'dependencies' => [
				'jquery.spinner',
				'mediawiki.notify'
			],
		],

		'wikibase.client.PageConnector' => $moduleTemplate + [
			'scripts' => [
				'wikibase.client.PageConnector.js'
			],
			'dependencies' => [
				'wikibase.sites'
			],
		],

		'jquery.wikibase.linkitem' => $moduleTemplate + [
			'scripts' => [
				'jquery.wikibase/jquery.wikibase.linkitem.js'
			],
			'styles' => [
				'jquery.wikibase/jquery.wikibase.linkitem.css'
			],
			'dependencies' => [
				'jquery.spinner',
				'jquery.ui.dialog',
				'jquery.ui.suggester',
				'jquery.wikibase.siteselector',
				'jquery.wikibase.wbtooltip',
				'mediawiki.api',
				'mediawiki.util',
				'mediawiki.jqueryMsg',
				'jquery.event.special.eachchange',
				'wikibase.client.currentSite',
				'wikibase.sites',
				'wikibase.api.RepoApi',
				'wikibase.api.RepoApiError',
				'wikibase.client.PageConnector'
			],
			'messages' => [
				'wikibase-error-unexpected',
				'wikibase-linkitem-alreadylinked',
				'wikibase-linkitem-title',
				'wikibase-linkitem-linkpage',
				'wikibase-linkitem-selectlink',
				'wikibase-linkitem-input-site',
				'wikibase-linkitem-input-page',
				'wikibase-linkitem-confirmitem-text',
				'wikibase-linkitem-confirmitem-button',
				'wikibase-linkitem-success-link',
				'wikibase-linkitem-close',
				'wikibase-linkitem-not-loggedin-title',
				'wikibase-linkitem-not-loggedin',
				'wikibase-linkitem-failure',
				'wikibase-replicationnote',
				'wikibase-sitelinks-sitename-columnheading',
				'wikibase-sitelinks-link-columnheading'
			],
		],

		'wikibase.client.action.edit.collapsibleFooter' => $moduleTemplate + [
			'scripts' => 'wikibase.client.action.edit.collapsibleFooter.js',
			'dependencies' => [
				'jquery.makeCollapsible',
				'mediawiki.storage',
				'mediawiki.icon',
			],
		]
	];
} );
