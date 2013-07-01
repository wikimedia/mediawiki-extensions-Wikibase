<?php

return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/client/resources'
	);

	return array(
		'wikibase.client.init' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => 'wikibase.client.css',
		),
		'wikibase.client.nolanglinks' => $moduleTemplate + array(
			'position' => 'top',
			'styles' => 'wikibase.client.nolanglinks.css',
		),
		'wikibase.client.currentSite' => $moduleTemplate + array(
			'class' => 'Wikibase\SiteModule'
		),
		'wikibase.client.page-move' => $moduleTemplate + array(
			'styles' => 'wikibase.client.page-move.css'
		),
		'wbclient.watchlist.css' => $moduleTemplate + array(
			'styles' => 'wbclient.watchlist.css',
			'position' => 'top',
		),
		'wikibase.client.linkitem.init' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase.client.linkitem.init.js'
			),
			'messages' => array(
				'wikibase-linkitem-addlinks',
				'unknown-error'
			),
			'dependencies' => array(
				'jquery.spinner',
				'mediawiki.notify'
			),
		),
		'jquery.wikibase.linkitem' => $moduleTemplate + array(
			'scripts' => array(
				'jquery.wikibase/jquery.wikibase.linkitem.js'
			),
			'styles' => array(
				'jquery.wikibase/jquery.wikibase.linkitem.css'
			),
			'dependencies' => array(
				'jquery.spinner',
				'jquery.ui.dialog',
				'jquery.ui.suggester',
				'jquery.wikibase.siteselector',
				'mediawiki.api',
				'mediawiki.util',
				'mediawiki.Title',
				'mediawiki.jqueryMsg',
				'wikibase.client.currentSite',
				'wikibase.sites',
				'wikibase.RepoApi',
				'wikibase.RepoApiError',
				'wikibase.ui.Tooltip'
			),
			'messages' => array(
				'wikibase-linkitem-alreadylinked',
				'wikibase-linkitem-title',
				'wikibase-linkitem-linkpage',
				'wikibase-linkitem-selectlink',
				'wikibase-linkitem-input-site',
				'wikibase-linkitem-input-page',
				'wikibase-linkitem-invalidsite',
				'wikibase-linkitem-confirmitem-text',
				'wikibase-linkitem-confirmitem-button',
				'wikibase-linkitem-success-create',
				'wikibase-linkitem-success-link',
				'wikibase-linkitem-close',
				'wikibase-linkitem-not-loggedin-title',
				'wikibase-linkitem-not-loggedin',
				'wikibase-linkitem-failure',
				'wikibase-replicationnote',
				'wikibase-sitelinks-sitename-columnheading',
				'wikibase-sitelinks-link-columnheading'
			),
		)
	);

} );
