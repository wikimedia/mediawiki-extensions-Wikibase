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

// enable data value normalization
$wgWBRepoSettings['tmpNormalizeDataValues'] = true;

// enable Federated Properties. With only local (= db) Entity Sources, this should have no effect.
$wgWBRepoSettings['federatedPropertiesEnabled'] = true;
// Overriding the default source URL so that no default API Entity Source gets added via DefaultFederatedPropertiesEntitySourceAdder
$wgWBRepoSettings['federatedPropertiesSourceScriptUrl'] = 'https://wikidata.beta.wmflabs.org/w/';

// make sitelinks to the current wiki work
$wgWBRepoSettings['siteLinkGroups'][] = 'CI';

$originalBadgeItems = $wgWBRepoSettings['badgeItems'] ?? [];
$originalRedirectBadgeItems = $wgWBRepoSettings['redirectBadgeItems'] ?? [];
$wgWBRepoSettings['badgeItems'] = static function () use ( $originalBadgeItems ) {
	global $wgRequest;

	$badges = $wgRequest->getHeader( 'X-Wikibase-CI-Badges', WebRequest::GETHEADER_LIST ) ?: [];
	return $originalBadgeItems + array_fill_keys( $badges, 'CI-badge-class' );
};
$wgWBRepoSettings['redirectBadgeItems'] = static function () use ( $originalRedirectBadgeItems ) {
	global $wgRequest;

	return array_merge(
		$originalRedirectBadgeItems,
		$wgRequest->getHeader( 'X-Wikibase-CI-Redirect-Badges', WebRequest::GETHEADER_LIST ) ?: []
	);
};
unset( $originalBadgeItems );
unset( $originalRedirectBadgeItems );
