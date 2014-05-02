<?php

namespace Wikibase;

use Wikibase\BadgesParsingException;
use Wikibase\DataModel\Entity\EntityIdParser;

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
	 * @var string[]
	 */
	private $allowedBadgeItems;

	/**
	 * @param EntityIdPaser $entityIdParser
	 * @param string[] $allowedBadgeItems
	 */
	public function __construct( EntityIdParser $entityIdParser, array $allowedBadgeItems ) {
		$this->entityIdParser = $entityIdParser;
		$this->allowedBadgeItems = $allowedBadgeItems;
	}

	/**
	 * @param string[] $badgeIds
	 *
	 * @return ItemId[]
	 * @throws BadgesParsingException
	 */
	public function parse( array $badgeIds ) {
		$badgesObjects = array();

		foreach ( $badgeIds as $prefixedId ) {
			$this->isAllowedBadgeItemId( $prefixedId );
			$badgesObjects[] = $this->extractItemId( $prefixedId );
		}

		return $badgesObjects;
	}

	/**
	 * @param string $prefixedId
	 *
	 * @return ItemId
	 * @throws BadgesParsingException
	 */
	private function extractItemId( $prefixedId ) {
		try {
			$badgeId = $this->entityIdParser->parse( $prefixedId );
		} catch ( \EntityIdParsingException $ex ) {
			throw new BadgesParsingException(
				'wikibase-setsitelink-not-item',
				$prefixedId,
				$prefixedId . ' is not a valid item ID.'
			);
		}

		if ( !( $badgeId instanceof ItemId ) ) {
			throw new BadgesParsingException(
				'wikibase-setsitelink-not-item',
				$badgeId->getSerialization(),
				$badgeId->getSerialization() . ' is not an item ID.'
			);
		}

		return $badgeId;
	}

	/**
	 * @param string $prefixedId
	 *
	 * @throws MessageException
	 */
	private function isAllowedBadgeItemId( $prefixedId ) {
		if ( !array_key_exists( $prefixedId, $this->allowedBadgeItems ) ) {
			throw new BadgesParsingException(
				'wikibase-setsitelink-not-badge',
				$prefixedId,
				$prefixedId . ' is not an allowed badge item.'
			);
		}
	}

}
