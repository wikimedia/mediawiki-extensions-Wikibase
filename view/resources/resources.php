<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	return array_merge(
		include __DIR__ . '/jquery/resources.php',
		include __DIR__ . '/jquery/ui/resources.php',
		include __DIR__ . '/jquery/wikibase/resources.php',
		include __DIR__ . '/wikibase/resources.php',
		include __DIR__ . '/wikibase/entityChangers/resources.php',
		include __DIR__ . '/wikibase/entityIdFormatter/resources.php',
		include __DIR__ . '/wikibase/store/resources.php',
		include __DIR__ . '/wikibase/utilities/resources.php',
		include __DIR__ . '/wikibase/view/resources.php'
	);
} );
