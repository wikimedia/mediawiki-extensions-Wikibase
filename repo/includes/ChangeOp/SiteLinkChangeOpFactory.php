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
	private $badgeItemIds;

	/**
	 * @param string[] $badgeItemIds
	 */
	public function __construct( array $badgeItemIds ) {
		$this->badgeItemIds = $badgeItemIds;
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
		return new ChangeOpSiteLink( $siteId, $pageName, $badges, $this->badgeItemIds );
	}

	/**
	 * @param string $siteId
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveSiteLinkOp( $siteId ) {
		return new ChangeOpSiteLink( $siteId, null );
	}

}
