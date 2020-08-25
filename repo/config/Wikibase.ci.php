<?php

/**
 * CI configuration for the Wikibase Repo extension.
 *
 * Largely uses the example config for testing,
 * with some settings that are not part of the default example yet
 * and also some overrides specific to browser tests.
 *
 * This file is NOT an entry point the Wikibase extension.
 * It should not be included from outside the extension.
 *
 * @see docs/options.wiki
 *
 * @license GPL-2.0-or-later
 */

require __DIR__ . '/Wikibase.example.php';

// Wikibase Cirrus search should not be used in browser tests
$wgWBCSUseCirrus = false;

// CirrusSearch should not perform any updates
$wgCirrusSearchDisableUpdate = true;

// enable data bridge
$wgWBRepoSettings['dataBridgeEnabled'] = true;

// enable tainted-refs
$wgWBRepoSettings['taintedReferencesEnabled'] = true;
