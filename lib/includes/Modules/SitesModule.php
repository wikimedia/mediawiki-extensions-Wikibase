<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Modules;

use BagOStuff;
use InvalidArgumentException;
// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;
use MediaWikiSite;
use MessageLocalizer;
use Site;
use SiteLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\SettingsArray;

/**
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Werner < daniel.a.r.werner@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class SitesModule extends RL\Module {

	protected $targets = [ 'desktop', 'mobile' ];

	/**
	 * How many seconds the result of getSiteDetails() is cached.
	 */
	private const SITE_DETAILS_TTL = 3600; // 1 hour

	/**
	 * @var SettingsArray
	 */
	private $clientSettings;

	/**
	 * @var SettingsArray
	 */
	private $repoSettings;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var BagOStuff
	 */
	private $cache;

	/**
	 * @var LanguageNameLookupFactory
	 */
	private $languageNameLookupFactory;

	/**
	 * @param SettingsArray|null $clientSettings The Client settings, if Client is enabled, else null.
	 * @param SettingsArray|null $repoSettings The Repo settings, if Repo is enabled, else null.
	 * @param SiteLookup $siteLookup
	 * @param BagOStuff $cache
	 * @param LanguageNameLookupFactory $languageNameLookupFactory
	 */
	public function __construct(
		?SettingsArray $clientSettings,
		?SettingsArray $repoSettings,
		SiteLookup $siteLookup,
		BagOStuff $cache,
		LanguageNameLookupFactory $languageNameLookupFactory
	) {
		$this->clientSettings = $clientSettings ?: new SettingsArray();
		$this->repoSettings = $repoSettings ?: new SettingsArray();
		$this->siteLookup = $siteLookup;
		$this->cache = $cache;
		$this->languageNameLookupFactory = $languageNameLookupFactory;
	}

	/**
	 * Used to propagate information about sites to JavaScript.
	 * Sites infos will be available in 'wbSiteDetails' config var.
	 * @see RL\Module::getScript
	 *
	 * @param RL\Context $context
	 * @return string JavaScript Code
	 */
	public function getScript( RL\Context $context ): string {
		$languageCode = $context->getLanguage();
		return $this->cache->getWithSetCallback(
			$this->cache->makeKey( 'wikibase-sites-module', 'script', $languageCode ),
			self::SITE_DETAILS_TTL,
			function () use ( $context ) {
				return $this->makeScript( $context );
			}
		);
	}

	public function enableModuleContentVersion(): bool {
		// Let RL\Module::getVersionHash() invoke getScript() and hash that.
		return true;
	}

	/**
	 * @param MessageLocalizer $localizer
	 * @return string JavaScript Code
	 */
	protected function makeScript( MessageLocalizer $localizer ): string {
		$groups = $this->getSetting( 'siteLinkGroups' );
		$specialGroups = $this->getSetting( 'specialSiteLinkGroups' );
		$specialPos = array_search( 'special', $groups );
		if ( $specialPos !== false ) {
			// The "special" group actually maps to multiple groups
			array_splice( $groups, $specialPos, 1, $specialGroups );
		}

		$siteDetails = [];
		/**
		 * @var MediaWikiSite $site
		 */
		foreach ( $this->siteLookup->getSites() as $site ) {
			if ( $this->shouldSiteBeIncluded( $site, $groups ) ) {
				$siteDetails[$site->getGlobalId()] = $this->computeSiteDetails(
					$site,
					$specialGroups,
					$localizer
				);
			}
		}

		return ResourceLoader::makeConfigSetScript( [ 'wbSiteDetails' => $siteDetails ] );
	}

	/**
	 * Get a setting from the repo or client settings, with repo overriding client.
	 */
	private function getSetting( string $settingName ) {
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
	 * @return string[]
	 */
	private function computeSiteDetails( MediaWikiSite $site, array $specialGroups, MessageLocalizer $localizer ): array {
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
		list( $pageUrl, $apiUrl ) = preg_replace(
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
	 * Whether it's needed to add a Site to the JS variable.
	 *
	 * @param Site $site
	 * @param string[] $groups
	 * @return bool
	 */
	private function shouldSiteBeIncluded( Site $site, array $groups ): bool {
		return $site->getType() === Site::TYPE_MEDIAWIKI && in_array( $site->getGroup(), $groups );
	}

}
