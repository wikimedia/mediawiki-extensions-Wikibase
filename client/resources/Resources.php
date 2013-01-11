<?php

return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => dirname( __FILE__ ),
		'remoteExtPath' => 'Wikibase/client/resources'
	);

	return array(
		'wikibase.client.init' => $moduleTemplate + array(
			'styles' => 'wikibase.client.css',
		),
		'wikibase.client.page-move' => $moduleTemplate + array(
			'styles' => 'wikibase.client.page-move.css'
		),
		'wikibase-client.watchlist.css' => $moduleTemplate + array(
			'styles' => 'wikibase-client.watchlist.css',
			'position' => 'top',
		),
		'wikibase-client.watchlist' => $moduleTemplate + array(
			'scripts' => array(
				'wikibase-client.watchlist.js'
			),
			'messages' => array(
				'hide',
				'show',
				'wbc-rc-hide-wikidata',
			),
		),
	);

} );
