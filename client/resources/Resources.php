<?php

return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => __DIR__,
		'remoteExtPath' => 'Wikibase/client/resources'
	);

	return array(
		'wikibase.client.init' => $moduleTemplate + array(
			'styles' => 'wikibase.client.css',
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
			'messages' => array(
				'hide',
				'show',
				'wbc-rc-hide-wikidata',
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
				'wikibase.client.currentSite',
				'wikibase.sites',
				'wikibase.store',
				'wikibase.ui.PropertyEditTool'
			),
			'messages' => array(
				'wb-sitelink',
				'wikibase-sitelinks-sitename-columnheading',
				'wikibase-sitelinks-siteid-columnheading',
				'wikibase-sitelinks-link-columnheading'
			),
		)
	);

} );
