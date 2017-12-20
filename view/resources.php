<?php

/**
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
return call_user_func( function() {
	return array_merge(
		include __DIR__ . '/lib/resources.php',
		include __DIR__ . '/resources/resources.php'
	);
} );
