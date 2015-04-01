<?php

/**
 * @license GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
return call_user_func( function() {
	return array_merge(
		include ( __DIR__ . '/wikibase/view/resources.php' )
	);
} );
