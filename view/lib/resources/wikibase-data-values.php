<?php

/**
 * @license GPL-2.0-or-later
 */
return call_user_func( function() {
	return array_merge(
		require __DIR__ . '/wikibase-data-values/src/resources.php',
		require __DIR__ . '/wikibase-data-values/lib/resources.php'
	);
} );
