<?php

namespace Wikibase\Client\DataAccess;

use BadMethodCallException;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * A lookup that resolves a specific sitelink on a specific Item into a MediaWiki Title object.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LocalSiteLinkTitleLookup implements EntityTitleLookup {

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $localSiteId;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $localSiteId
	 */
	public function __construct(
		SiteLinkLookup $siteLinkLookup,
		$localSiteId
	) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->localSiteId = $localSiteId;
	}

	/**
	 * @see EntityTitleLookup::getTitleForId
	 *
	 * @since 0.5
	 *
	 * @param EntityId $id
	 *
	 * @return Title|null
	 */
	public function getTitleForId( EntityId $id ) {
		if ( !( $id instanceof ItemId ) ) {
			return null;
		}

		return Title::newFromText( $this->getLocalPageNameForItem( $id ) );
	}

	/**
	 * @param ItemId $id
	 *
	 * @return string|null
	 */
	public function getLocalPageNameForItem( ItemId $id ) {
		// TODO: Bad, bad interface
		$siteLinkData = $this->siteLinkLookup->getLinks(
			[ $id->getNumericId() ],
			[ $this->localSiteId ]
		);

		if ( count( $siteLinkData ) !== 1 ) {
			return null;
		}

		return $siteLinkData[0][1];
	}

	/**
	 * @see EntityTitleLookup::getNamespaceForType
	 *
	 * @param string $entityType
	 *
	 * @throws BadMethodCallException
	 * @return int
	 */
	public function getNamespaceForType( $entityType ) {
		throw new BadMethodCallException( 'Undefined in this implementation' );
	}

}
