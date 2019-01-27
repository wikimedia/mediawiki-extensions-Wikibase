<?php

/**
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	return array_merge(
		require __DIR__ . '/jquery/resources.php',
		require __DIR__ . '/jquery/wikibase/resources.php',
		require __DIR__ . '/wikibase/resources.php',
		require __DIR__ . '/wikibase/entityChangers/resources.php',
		require __DIR__ . '/wikibase/entityIdFormatter/resources.php',
		require __DIR__ . '/wikibase/store/resources.php',
		require __DIR__ . '/wikibase/utilities/resources.php',
		require __DIR__ . '/wikibase/view/resources.php'
	);
} );
