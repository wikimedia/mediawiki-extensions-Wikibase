<?php

namespace Wikibase\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * Factory for ChangeOps that modify SiteLinks.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class SiteLinkChangeOpFactory {

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @param string[] $badgeItems
	 */
	public function __construct( array $badgeItems ) {
		$this->badgeItems = $badgeItems;
	}

	/**
	 * @param string $siteId
	 * @param string $pageName
	 * @param ItemId[]|null $badges
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newSetSiteLinkOp( $siteId, $pageName, array $badges = null ) {
		return new ChangeOpSiteLink( $siteId, $pageName, $this->badgeItems, $badges );
	}

	/**
	 * @param string $siteId
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveSiteLinkOp( $siteId ) {
		return new ChangeOpSiteLink( $siteId, null, $this->badgeItems );
	}

}
