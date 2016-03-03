<?php

/**
 * Configuration for the Wikibase Repo extension for use with Jenkins CI.
 *
 * This file is NOT an entry point the Wikibase Repo extension. Use Wikibase.php.
 * It should furthermore not be included from outside the extension.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */

if ( !defined( 'WB_VERSION' ) ) {
	die( 'Not an entry point. Load Wikibase.php first.' );
}

// Load example settings:
include __DIR__ . '/Wikibase.example.php';

// Apply additional settings for Jenkins CI:

// ...

