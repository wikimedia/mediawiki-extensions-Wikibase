<?php

/**
 * @license GPL-2.0+
 */
return call_user_func( function() {
	global $wgMessagesDirs;

	$wgMessagesDirs['WikibaseView'] = array_merge(
		$wgMessagesDirs['WikibaseView'],
		[ __DIR__ . '/wikibase-data-values-value-view/i18n' ]
	);
} );
