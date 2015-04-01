<?php

/**
 * @license GNU GPL v2+
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
return array_merge(
	include( __DIR__ . '/jquery/resources.php' ),
	include( __DIR__ . '/jquery/ui/resources.php' ),
	include( __DIR__ . '/wikibase/entityChangers/resources.php' ),
	include( __DIR__ . '/wikibase/store/resources.php' ),
	include( __DIR__ . '/wikibase/view/resources.php' )
);
