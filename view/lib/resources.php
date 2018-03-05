<?php

/**
 * @license GPL-2.0-or-later
 */
return call_user_func( function() {
	return array_merge(
		include __DIR__ . '/resources/wikibase-data-model.php',
		include __DIR__ . '/resources/wikibase-data-values.php',
		include __DIR__ . '/resources/wikibase-serialization.php',
		include __DIR__ . '/resources/wikibase-api.php',
		include __DIR__ . '/resources/wikibase-data-values-value-view.php'
	);
} );
