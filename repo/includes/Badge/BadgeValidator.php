<?php

namespace Wikibase\Badge;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityLookup;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BadgeValidator {

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string[]
	 */
	private $allowedBadgeItems;

	/**
	 * @param EntityLookup $entityLookup
	 * @param string[] $allowedBadgeItems
	 */
	public function __construct( EntityLookup $entityLookup, array $allowedBadgeItems ) {
		$this->entityLookup = $entityLookup;
		$this->allowedBadgeItems = $allowedBadgeItems;
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @throws BadgeException
	 */
	public function validate( ItemId $itemId ) {
		$this->validateIsAllowedBadgeItemId( $itemId );
		$this->validateIsExistingItem( $itemId );
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @throws BadgeException
	 */
	private function validateIsAllowedBadgeItemId( ItemId $itemId ) {
		$prefixedId = $itemId->getSerialization();

		if ( !$this->isAllowedBadgeItemId( $prefixedId ) ) {
			throw new BadgeException(
				'wikibase-badge-not-allowed',
				$prefixedId,
				$prefixedId . ' is not an allowed badge item.'
			);
		}
	}

	/**
	 * @param string $prefixedId
	 *
	 * @return boolean
	 */
	private function isAllowedBadgeItemId( $prefixedId ) {
		return array_key_exists( $prefixedId, $this->allowedBadgeItems );
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @throws BadgeParsingException
	 */
	private function validateIsExistingItem( ItemId $itemId ) {
		if ( !$this->isExistingItem( $itemId ) ) {
			$prefixedId = $itemId->getSerialization();

			throw new BadgeException(
				'wikibase-badge-item-not-exist',
				$prefixedId,
				$prefixedId . ' item does not exist.'
			);
		}
	}

	/**
	 * @param ItemId $itemId
	 *
	 * @return boolean
	 */
	private function isExistingItem( ItemId $itemId ) {
		return $this->entityLookup->hasEntity( $itemId );
	}

}
