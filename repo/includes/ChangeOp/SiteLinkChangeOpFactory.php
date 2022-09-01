<?php

namespace Wikibase\Repo\ChangeOp;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikimedia\Assert\Assert;

/**
 * Factory for ChangeOps that modify SiteLinks.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class SiteLinkChangeOpFactory {

	/**
	 * @var string[]
	 */
	private $allowedBadgeItemIds;

	/**
	 * @param string[] $allowedBadgeItemIds
	 */
	public function __construct( array $allowedBadgeItemIds ) {
		Assert::parameterElementType( 'string', $allowedBadgeItemIds, '$allowedBadgeItemIds' );

		$this->allowedBadgeItemIds = $allowedBadgeItemIds;
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
		if ( $badges !== null ) {
			$this->validateBadges( $badges );
		}

		return new ChangeOpSiteLink( $siteId, $pageName, $badges );
	}

	/**
	 * @param string $siteId
	 *
	 * @throws InvalidArgumentException
	 * @return ChangeOp
	 */
	public function newRemoveSiteLinkOp( $siteId ) {
		return new ChangeOpRemoveSiteLink( $siteId );
	}

	/**
	 * @param ItemId[] $badges
	 *
	 * @throws InvalidArgumentException
	 * @return ItemId[]
	 */
	private function validateBadges( array $badges ) {
		$uniqueBadges = [];

		foreach ( $badges as $id ) {
			if ( !( $id instanceof ItemId ) ) {
				throw new InvalidArgumentException( '$badges needs to be an array of ItemId instances' );
			}

			if ( !in_array( $id->getSerialization(), $this->allowedBadgeItemIds ) ) {
				throw new InvalidArgumentException( 'Item ID is not allowed as a badge: ' . $id->getSerialization() );
			}

			$uniqueBadges[$id->getSerialization()] = $id;
		}

		return array_values( $uniqueBadges );
	}

}
