<?php

/**
 * CI configuration for the Wikibase Client extension.
 *
 * Largely uses the example config for testing
 * but adds settings not to be part of the default example yet
 *
 * This file is NOT an entry point the Wikibase Client extension. Use WikibaseClient.php.
 * It should furthermore not be included from outside the extension.
 *
 * @see docs/options.wiki
 *
 * @license GPL-2.0-or-later
 */

require __DIR__ . '/WikibaseClient.example.php';

$wgWBClientSettings['dataBridgeEnabled'] = true;
$wgWBClientSettings['dataBridgeHrefRegExp'] = '[/=](?:Item:)?(Q[1-9][0-9]*).*#(P[1-9][0-9]*)$';
$wgWBClientSettings['dataBridgeEditTags'] = [ 'Data Bridge' ];
