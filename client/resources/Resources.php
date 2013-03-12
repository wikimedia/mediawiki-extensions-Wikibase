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
		'wbclient.watchlist' => $moduleTemplate + array(
			'scripts' => array(
				'wbclient.watchlist.js'
			),
			'dependencies' => array(
				'user.options'
			),
			'messages' => array(
				'hide',
				'show',
				'wikibase-rc-hide-wikidata',
			),
		),
		'wbclient.linkItem' => $moduleTemplate + array(
			'scripts' => array(
				'wbclient.linkItem.js'
			),
			'styles' => array(
				'wbclient.linkItem.css'
			),
			'dependencies' => array(
				'jquery.spinner',
				'jquery.ui.dialog',
				'jquery.ui.suggester',
				'jquery.wikibase.siteselector',
				'mediawiki.api',
				'mediawiki.jqueryMsg',
				'wikibase.client.currentSite',
				'wikibase.sites',
				'wikibase.store',
				'wikibase.ui.Tooltip'
			),
			'messages' => array(
				'wikibase-linkitem-addlinks',
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
