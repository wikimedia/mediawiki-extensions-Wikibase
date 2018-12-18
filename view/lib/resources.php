<?php

/**
 * @license GPL-2.0-or-later
 */
return call_user_func( function() {
        return array_merge(
			require __DIR__ . '/resources/wikibase-data-model.php',
			require __DIR__ . '/resources/wikibase-data-values.php',
			require __DIR__ . '/resources/wikibase-serialization.php',
			require __DIR__ . '/resources/wikibase-api.php',
			require __DIR__ . '/resources/wikibase-data-values-value-view.php',
			require __DIR__ . '/resources/wikibase-termbox.php'
		);
} );
