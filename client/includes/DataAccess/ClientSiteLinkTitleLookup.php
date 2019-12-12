<?php

namespace Wikibase\Client\DataAccess;

use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * A lookup that resolves a specific sitelink on a specific Item into a MediaWiki Title object.
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ClientSiteLinkTitleLookup implements EntityTitleLookup {

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $clientSiteId;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $clientSiteId
	 */
	public function __construct( SiteLinkLookup $siteLinkLookup, $clientSiteId ) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->clientSiteId = $clientSiteId;
	}

	/**
	 * @see EntityTitleLookup::getTitleForId
	 *
	 * @param EntityId $id
	 *
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $id ) {
		if ( !( $id instanceof ItemId ) ) {
			return null;
		}

		$pageName = $this->getPageNameForItem( $id );

		if ( $pageName === null ) {
			return null;
		}

		return Title::newFromText( $pageName );
	}

	/**
	 * @param ItemId $id
	 *
	 * @return string|null
	 */
	private function getPageNameForItem( ItemId $id ) {
		// TODO: SiteLinkLookup::getLinks does have a bad, bad interface.
		$siteLinkData = $this->siteLinkLookup->getLinks(
			[ $id->getNumericId() ],
			[ $this->clientSiteId ]
		);

		if ( count( $siteLinkData ) !== 1 ) {
			return null;
		}

		return $siteLinkData[0][1];
	}

	/**
	 * @inheritDoc
	 */
	public function getTitlesForIds( array $ids ) {
		$result = [];
		/** @var EntityId $id */
		foreach ( $ids as $id ) {
			$result[$id->getSerialization()] = $this->getTitleForId( $id );
		}

		return $result;
	}

}
