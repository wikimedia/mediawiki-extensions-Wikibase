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

use MediaWiki\Context\RequestContext;

require_once __DIR__ . '/Wikibase.example.php';

// Wikibase Cirrus search should not be used in browser tests
$wgWBCSUseCirrus = false;

// CirrusSearch should not perform any updates
$wgCirrusSearchDisableUpdate = true;

// use mysql-upsert if CI is using a MySQL database to avoid deadlocks from parallel tests
$wgWBRepoSettings['idGenerator'] = 'auto';

// enable data bridge
$wgWBRepoSettings['dataBridgeEnabled'] = true;

// enable tainted-refs
$wgWBRepoSettings['taintedReferencesEnabled'] = true;

// enable Wikibase REST API (both the production-ready and work-in-progress routes)
$wgRestAPIAdditionalRouteFiles[] = 'extensions/Wikibase/repo/rest-api/routes.json';
$wgRestAPIAdditionalRouteFiles[] = 'extensions/Wikibase/repo/rest-api/routes.dev.json';

// enable Federated Properties. With only local (= db) Entity Sources, this should have no effect.
$wgWBRepoSettings['federatedPropertiesEnabled'] = true;
// Overriding the default source URL so that no default API Entity Source gets added via DefaultFederatedPropertiesEntitySourceAdder
$wgWBRepoSettings['federatedPropertiesSourceScriptUrl'] = 'https://wikidata.beta.wmflabs.org/w/';

// make sitelinks to the current wiki work
$wgWBRepoSettings['siteLinkGroups'][] = 'CI';

$wgWBRepoSettings['tmpFixRdfSomevalueHash'] = true; // T384344

// This is a dangerous hack that should never ever be done on any production wiki. It enables e2e tests for config-dependent behavior.
$configOverrides = json_decode( RequestContext::getMain()->getRequest()->getHeader( 'X-Config-Override' ) ?: '{}', true );
foreach ( $configOverrides as $name => $value ) {
	$GLOBALS[$name] = is_array( $value ) ? array_merge( $GLOBALS[$name] ?? [], $value ) : $value;
}
