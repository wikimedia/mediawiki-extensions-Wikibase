<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Modules;

use InvalidArgumentException;
// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use MediaWikiSite;
use MessageLocalizer;
use Site;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
abstract class SitesModuleBase extends RL\Module {

	/**
	 * @var SettingsArray
	 */
	private $clientSettings;

	/**
	 * @var SettingsArray
	 */
	private $repoSettings;

	/**
	 * @var LanguageNameLookupFactory
	 */
	private $languageNameLookupFactory;

	/**
	 * @param SettingsArray|null $clientSettings The Client settings, if Client is enabled, else null.
	 * @param SettingsArray|null $repoSettings The Repo settings, if Repo is enabled, else null.
	 * @param LanguageNameLookupFactory $languageNameLookupFactory
	 */
	public function __construct(
		?SettingsArray $clientSettings,
		?SettingsArray $repoSettings,
		LanguageNameLookupFactory $languageNameLookupFactory
	) {
		$this->clientSettings = $clientSettings ?: new SettingsArray();
		$this->repoSettings = $repoSettings ?: new SettingsArray();
		$this->languageNameLookupFactory = $languageNameLookupFactory;
	}

	public function enableModuleContentVersion(): bool {
		// Let RL\Module::getVersionHash() invoke getScript() and hash that.
		return true;
	}

	/**
	 * Get a setting from the repo or client settings, with repo overriding client.
	 * @return mixed
	 */
	public function getSetting( string $settingName ) {
		if ( $this->repoSettings->hasSetting( $settingName ) ) {
			return $this->repoSettings->getSetting( $settingName );
		}
		if ( $this->clientSettings->hasSetting( $settingName ) ) {
			return $this->clientSettings->getSetting( $settingName );
		}
		throw new InvalidArgumentException(
			"Setting $settingName is missing from both Repo and Client settings!"
		);
	}

	/**
	 * @param MediaWikiSite $site
	 * @param string[] $specialGroups
	 * @param MessageLocalizer $localizer
	 * @return array
	 */
	public function computeSiteDetails( MediaWikiSite $site, array $specialGroups, MessageLocalizer $localizer ): array {
		$languageNameLookup = $this->languageNameLookupFactory->getForAutonyms();

		// FIXME: quickfix to allow a custom site-name / handling for the site groups which are
		// special according to the specialSiteLinkGroups setting
		$group = $site->getGroup();
		if ( in_array( $group, $specialGroups ) ) {
			$name = $this->getSpecialSiteLanguageName( $site, $localizer );
			$groupName = 'special';
		} else {
			$languageCode = $site->getLanguageCode();
			if ( $languageCode !== null ) {
				$name = $languageNameLookup->getName( $languageCode );
			} else {
				$name = $site->getGlobalId(); // better than nothing
			}
			$groupName = $group;
		}

		// Use protocol relative URIs, as it's safe to assume that all wikis support the same protocol
		[ $pageUrl, $apiUrl ] = preg_replace(
			"/^https?:/i",
			'',
			[
				$site->getPageUrl(),
				$site->getFileUrl( 'api.php' ),
			]
		);

		return [
			'shortName' => $name,
			'name' => $name, // use short name for both, for now
			'id' => $site->getGlobalId(),
			'pageUrl' => $pageUrl,
			'apiUrl' => $apiUrl,
			'languageCode' => $site->getLanguageCode(),
			'group' => $groupName,
		];
	}

	/**
	 * @param Site $site
	 * @param MessageLocalizer $localizer
	 * @return string
	 */
	private function getSpecialSiteLanguageName( Site $site, MessageLocalizer $localizer ): string {
		$siteId = $site->getGlobalId();
		$messageKey = 'wikibase-sitelinks-sitename-' . $siteId;

		$languageNameMsg = $localizer->msg( $messageKey );

		return $languageNameMsg->exists() ? $languageNameMsg->parse() : htmlspecialchars( $siteId );
	}

	/**
	 * @param MessageLocalizer $localizer
	 * @return string JavaScript Code
	 */
	abstract protected function makeScript( MessageLocalizer $localizer ): string;
}
