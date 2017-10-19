<?php

/**
 * @license GPL-2.0+
 */
return call_user_func( function() {
	return array_merge(
		include __DIR__ . '/wikibase-data-values/src/resources.php',
		include __DIR__ . '/wikibase-data-values/lib/resources.php'
	);
} );
