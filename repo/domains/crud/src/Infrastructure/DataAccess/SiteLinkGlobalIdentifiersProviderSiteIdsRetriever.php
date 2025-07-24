<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess;

use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Domains\Crud\Domain\Services\SiteIdsRetriever;
use Wikibase\Repo\SiteLinkGlobalIdentifiersProvider;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkGlobalIdentifiersProviderSiteIdsRetriever implements SiteIdsRetriever {

	public function __construct(
		private readonly SiteLinkGlobalIdentifiersProvider $siteLinkGlobalIdentifiersProvider,
		private readonly SettingsArray $repoSettings
	) {
	}

	public function getValidSiteIds(): array {
		return $this->siteLinkGlobalIdentifiersProvider->getList(
			$this->repoSettings->getSetting( 'siteLinkGroups' )
		);
	}
}
