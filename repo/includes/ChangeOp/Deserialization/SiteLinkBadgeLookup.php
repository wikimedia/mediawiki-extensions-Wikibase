<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * TODO: Class name!
 * TODO: Class desc
 *
 * @license GPL-2.0+
 */
class SiteLinkBadgeLookup {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var string[]
	 */
	private $badgeItems;

	/**
	 * @param EntityTitleLookup $titleLookup
	 * @param string[] $badgeItems
	 */
	public function __construct( EntityTitleLookup $titleLookup, array $badgeItems ) {
		$this->titleLookup = $titleLookup;
		$this->badgeItems = $badgeItems;
	}

	/**
	 * Validates badges from params and turns them into an array of ItemIds.
	 *
	 * @todo: extract this into a SiteLinkBadgeHelper
	 *
	 * @param string[] $badgesParams // TODO: rename
	 *
	 * @return ItemId[]
	 */
	public function parseSiteLinkBadgesSerialization( array $badgesParams ) {
		$badges = array();

		foreach ( $badgesParams as $badgeSerialization ) {
			try {
				$badgeId = new ItemId( $badgeSerialization );
			} catch ( InvalidArgumentException $ex ) {
				throw new ChangeOpDeserializationException( 'Badges: could not parse "' . $badgeSerialization
					. '", the id is invalid', 'invalid-entity-id' );
				continue;
			}

			if ( !array_key_exists( $badgeId->getSerialization(), $this->badgeItems ) ) {
				throw new ChangeOpDeserializationException( 'Badges: item "' . $badgeSerialization . '" is not a badge',
					'not-badge' );
			}

			$itemTitle = $this->titleLookup->getTitleForId( $badgeId );

			if ( is_null( $itemTitle ) || !$itemTitle->exists() ) {
				throw new ChangeOpDeserializationException(
					'Badges: no item found matching id "' . $badgeSerialization . '"',
					'no-such-entity'
				);
			}

			$badges[] = $badgeId;
		}

		return $badges;
	}

}