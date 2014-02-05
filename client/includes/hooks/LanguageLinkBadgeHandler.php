<?php

namespace Wikibase\Client\Hooks;

use Title;
use Wikibase\SiteLinkLookup;
use Wikibase\EntityLookup;
use Wikibase\DataModel\SiteLink;

/**
 * Provides access to the badges of the connected sitelinks of a page.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LanguageLinkBadgeHandler {

	/**
	 * @var string
	 */
	protected $siteId;

	/**
	 * @var SiteLinkLookup
	 */
	protected $siteLinkLookup;

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @param string $siteId Global id of the client wiki
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityLookup $entityLookup
	 */
	public function __construct( $siteId, SiteLinkLookup $siteLinkLookup, EntityLookup $entityLookup ) {
		$this->siteId = $siteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * Finds the corresponding item on the repository and
	 * returns the item's site link for the given site id.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param string $siteId
	 *
	 * @return SiteLink|null
	 */
	public function getSiteLink( Title $title, $siteId ) {
		$siteLink = new SiteLink( $this->siteId, $title->getFullText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId === null ) {
			return null;
		}

		$item = $this->entityLookup->getEntity( $itemId );
		if ( !$item->hasLinkToSite( $siteId ) ) {
			return null;
		}
		return $item->getSiteLink( $siteId );
	}

	/**
	 * Looks up the item of the given title and returns the badges
	 * of the sitelink associated with the given language link title.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param string $siteId
	 *
	 * @return string[]
	 */
	public function getBadges( Title $title, Title $languageLinkTitle ) {
		$siteId = $languageLinkTitle->getInterwiki()->getWikiID();
		$siteLink = $this->getSiteLink( $title, $siteId );
		if ( $siteLink === null ) {
			return array();
		}
		$badges = array();
		foreach ( $siteLink->getBadges() as $badge ) {
			$badges[] = $badge->getSerialization();
		}
		return $badges;
	}

}
