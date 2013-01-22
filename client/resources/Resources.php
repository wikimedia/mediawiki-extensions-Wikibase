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
			'class' => 'Wikibase\SiteModul'
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
			'dependencies' => array(
				'jquery.spinner',
				'jquery.ui.dialog',
				'wikibase.client.currentSite',
				'wikibase.store',
				'wikibase.templates',
			//	'wikibase.ui.PropertyEditTool'
			),
			'messages' => array(
				'wb-sitelink',
			),
		)
	);

} );
