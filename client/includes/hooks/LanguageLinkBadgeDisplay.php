<?php

namespace Wikibase\Client\Hooks;

use Site;
use Title;
use SiteStore;
use Wikibase\SiteLinkLookup;
use Wikibase\EntityLookup;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Provides access to the badges of the current page's sitelinks
 * and adds some properties to the HTML output to display them.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageLinkBadgeDisplay {

	/**
	 * @var string
	 */
	protected $localSiteId;

	/**
	 * @var SiteLinkLookup
	 */
	protected $siteLinkLookup;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var SiteList
	 */
	protected $sites;

	/**
	 * @var array
	 */
	protected $displayBadges;

	/**
	 * @var string
	 */
	protected $langCode;

	/**
	 * @var Site[]
	 */
	protected $sitesByNavigationId = null;

	/**
	 * @param string $localSiteId Global id of the client wiki
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param SiteList $sites
	 * @param array $displayBadges
	 * @param string $langCode
	 */
	public function __construct( $localSiteId, SiteLinkLookup $siteLinkLookup,
			EntityLookup $entityLookup, SiteList $sites, array $displayBadges, $langCode ) {
		$this->localSiteId = $localSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->sites = $sites;
		$this->displayBadges = $displayBadges;
		$this->langCode = $langCode;
	}

	/**
	 * Looks up the item of the given title and assigns the badges of the sitelink
	 * associated with the given language link title to the passed array.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param Title $languageLinkTitle
	 * @param array &$languageLink
	 */
	public function assignBadges( Title $title, Title $languageLinkTitle, array &$languageLink ) {
		$navId = $languageLinkTitle->getInterwiki();
		if ( !$this->sites->hasNavigationId( $navId ) ) {
			return array();
		}

		$site = $this->sites->getSiteByNavigationId( $navId );

		$siteLink = $this->getSiteLink( $title, $site->getGlobalId() );
		if ( !$siteLink ) {
			return array();
		}

		$linkBadges = array();
		foreach ( $siteLink->getBadges() as $badgeObject ) {
			$badge = $badgeObject->getSerialization();
			if ( !isset( $languageLink['class'] ) ) {
				$languageLink['class'] = "badge-$badge";
			} else {
				$languageLink['class'] .= " badge-$badge";
			}
			$linkBadges[] = $badge;
		}
		
		foreach ( $this->displayBadges as $badge ) {
			if ( in_array( $badge, $linkBadges ) ) {
				$title = $this->getTitle( $badge );
				if ( $title !== null ) {
					// if a badge comes later in the config,
					// this will override the title as documented.
					$languageLink['itemtitle'] = $title;
				}
			}
		}
	}

	/**
	 * Finds the corresponding item on the repository and
	 * returns the item's site link for the given site id.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param string $languageLinkSiteId
	 *
	 * @return SiteLink|null
	 */
	protected function getSiteLink( Title $title, $languageLinkSiteId ) {
		$siteLink = new SiteLink( $this->localSiteId, $title->getText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId === null ) {
			return null;
		}

		$item = $this->entityLookup->getEntity( $itemId );
		if ( !$item->hasLinkToSite( $languageLinkSiteId ) ) {
			return null;
		}
		return $item->getSiteLink( $languageLinkSiteId );
	}

	/**
	 * Returns the title for the given badge.
	 *
	 * @since 0.5
	 *
	 * @param string $badge
	 *
	 * @return string|null
	 */
	protected function getTitle( $badge ) {
		$entity = $this->entityLookup->getEntity( new ItemId( $badge ) );
		if ( !$entity ) {
			return null;
		}

		$description = $entity->getDescription( $this->langCode );
		if ( !$description ) {
			return null;
		}
		return $description;
	}

}
