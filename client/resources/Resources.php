<?php

return call_user_func( function() {

	$moduleTemplate = array(
		'localBasePath' => dirname( __FILE__ ),
		'remoteExtPath' => 'Wikibase/client/resources'
	);

	return array(
		'ext.wikibaseclient.init' => $moduleTemplate + array(
			'styles' => 'ext.wikibaseclient.css'
		),
	);

} );
