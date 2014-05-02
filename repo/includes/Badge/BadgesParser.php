<?php

namespace Wikibase\Badge;

use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class BadgesParser {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var BadgeValidator
	 */
	private $badgeValidator;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param BadgeValidator $badgeValidator
	 */
	public function __construct( EntityIdParser $entityIdParser, BadgeValidator $badgeValidator ) {
		$this->entityIdParser = $entityIdParser;
		$this->badgeValidator = $badgeValidator;
	}

	/**
	 * @param string[] $badgeIds
	 *
	 * @return ItemId[]
	 * @throws BadgeException
	 */
	public function parse( array $badgeIds ) {
		$badgesObjects = array();

		foreach ( $badgeIds as $prefixedId ) {
			$itemId = $this->parseBadgeItemId( $prefixedId );
			$this->badgeValidator->validate( $itemId );

			$badgesObjects[] = $itemId;
		}

		return $badgesObjects;
	}

	/**
	 * @param string $prefixedId
	 *
	 * @return ItemId
	 * @throws BadgeException
	 */
	private function parseBadgeItemId( $prefixedId ) {
		try {
			$badgeId = $this->entityIdParser->parse( $prefixedId );
		} catch ( EntityIdParsingException $ex ) {
			throw new BadgeException(
				'wikibase-setsitelink-not-item',
				$prefixedId,
				$prefixedId . ' is not a valid item ID.'
			);
		}

		$this->validateIsItemId( $badgeId );

		return $badgeId;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws BadgeException
	 */
	private function validateIsItemId( $badgeId ) {
		if ( !( $badgeId instanceof ItemId ) ) {
			throw new BadgeException(
				'wikibase-setsitelink-not-item',
				$badgeId->getSerialization(),
				$badgeId->getSerialization() . ' is not an item ID.'
			);
		}
	}

}
