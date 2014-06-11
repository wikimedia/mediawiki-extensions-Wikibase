<?php

namespace Wikibase;

use ParserOutput;
use Title;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Handles wikibase_item page and parser output property
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert
 */
class EntityIdPropertyUpdater {

	/* @var SiteLinkLookup */
	private $siteLinkLookup;

	/* @var string */
	private $siteId;

	/**
	 * @since 0.4
	 *
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $siteId
	 */
	public function __construct( SiteLinkLookup $siteLinkLookup, $siteId ) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
	}

	/**
	 * Set parser output property with item id
	 *
	 * @since 0.4
	 *
	 * @param ParserOutput $out
	 * @param Title $title
	 */
	public function updateItemIdProperty( ParserOutput $out, Title $title ) {
		$siteLink = new SiteLink(
			$this->siteId,
			$title->getFullText()
		);

		// todo: do we really want to fetch item id twice during parsing?
		$itemId = $this->siteLinkLookup->getEntityIdForSiteLink( $siteLink );

		if ( $itemId instanceof EntityId ) {
			$out->setProperty( 'wikibase_item', $itemId->getSerialization() );
		} else {
			$out->unsetProperty( 'wikibase_item' );

			wfDebugLog( __CLASS__, __FUNCTION__ . ': Trying to set wikibase_item property for '
				. $siteLink->getSiteId() . ':' . $siteLink->getPageName()
				. ' but $itemId ' . $itemId . ' is not an EntityId object.' );
		}
	}
}
