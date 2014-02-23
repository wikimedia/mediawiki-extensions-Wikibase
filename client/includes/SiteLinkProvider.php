<?php
namespace Wikibase\Client;

use Wikibase\SiteLinkLookup;
use Wikibase\EntityLookup;

/**
 * Provides access to sitelinks on repo.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SiteLinkProvider {

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
	 * @var SiteStore
	 */
	protected $sites;

	/**
	 * @var Site[]
	 */
	protected $sitesByNavigationId = null;

	/**
	 * @param string $localSiteId global id of the client wiki
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param EntityLookup $entityLookup
	 * @param SiteStore $sites
	 */
	public function __construct( $localSiteId, SiteLinkLookup $siteLinkLookup, EntityLookup $entityLookup, SiteStore $sites ) {
		$this->localSiteId = $localSiteId;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->entityLookup = $entityLookup;
		$this->sites = $sites;
	}

	/**
	 * Finds the corresponding item on the repository and returns the item's site links.
	 * The sitelinks optionally also include badges, although this is more expensive.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param bool? $includeBadges
	 *
	 * @return SiteLink[]
	 */
	protected function getSiteLinks( Title $title, $includeBadges = false ) {
		$siteLink = new SiteLink( $this->localSiteId, $title->getText() );
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId === null ) {
			return array();
		}

		// we need this check until there is a proper SiteLinkLookup which also provides badges
		if ( $includeBadges === false ) {
			return $this->siteLinkLookup->getSiteLinksForItem( $itemId );
		}

		$item = $this->entityLookup->getEntity( $itemId );
		if ( $item === null ) {
			return array();
		}
		return $item->getSiteLinks();
	}

	/**
	 * Returns a Site object for the given navigational ID (alias inter-language prefix).
	 *
	 * @todo: move this functionality into Sites/SiteList/SiteArray!
	 *        This snippet has been copied from LangLinkHandler until we have it in core.
	 *
	 * @param string $id The navigation ID to find a site for.
	 *
	 * @return bool|Site The site with the given navigational ID, or false if not found.
	 */
	public function getSiteByNavigationId( $id ) {
		wfProfileIn( __METHOD__ );

		//FIXME: this needs to be moved into core, into SiteList resp. SiteArray!
		if ( $this->sitesByNavigationId === null ) {
			$this->sitesByNavigationId = array();

			/* @var Site $site */
			foreach ( $this->sites->getSites() as $site ) {
				$ids = $site->getNavigationIds();

				foreach ( $ids as $navId ) {
					$this->sitesByNavigationId[$navId] = $site;
				}
			}
		}

		wfProfileOut( __METHOD__ );
		return isset( $this->sitesByNavigationId[$id] ) ? $this->sitesByNavigationId[$id] : false;
	}

}
