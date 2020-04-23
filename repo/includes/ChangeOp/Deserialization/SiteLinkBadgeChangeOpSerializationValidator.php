<?php

namespace Wikibase\Repo\ChangeOp\Deserialization;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;

/**
 * Validates the structure of the site link's badge change request.
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkBadgeChangeOpSerializationValidator {

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var string[]
	 */
	private $allowedBadgeItemIds;

	/**
	 * @param EntityTitleLookup $titleLookup
	 * @param string[] $allowedBadgeItemIds
	 */
	public function __construct( EntityTitleLookup $titleLookup, array $allowedBadgeItemIds ) {
		$this->titleLookup = $titleLookup;
		$this->allowedBadgeItemIds = $allowedBadgeItemIds;
	}

	/**
	 * @param string[] $serialization
	 *
	 * @throws ChangeOpDeserializationException
	 */
	public function validateBadgeSerialization( array $serialization ) {
		foreach ( $serialization as $badgeSerialization ) {
			if ( !is_string( $badgeSerialization ) ) {
				throw new ChangeOpDeserializationException(
					'Badges: a string was expected, but not found',
					'not-recognized-string'
				);
			}

			try {
				// TODO: this should be rather using EntityIdParser
				$badgeId = new ItemId( $badgeSerialization );
			} catch ( InvalidArgumentException $ex ) {
				throw new ChangeOpDeserializationException( 'Badges: could not parse "' . $badgeSerialization
					. '", the id is invalid', 'invalid-entity-id' );
			}

			if ( !in_array( $badgeId->getSerialization(), $this->allowedBadgeItemIds ) ) {
				throw new ChangeOpDeserializationException( 'Badges: item "' . $badgeSerialization . '" is not a badge',
					'not-badge' );
			}

			$itemTitle = $this->titleLookup->getTitleForId( $badgeId );

			if ( $itemTitle === null || !$itemTitle->exists() ) {
				throw new ChangeOpDeserializationException(
					'Badges: no item found matching id "' . $badgeSerialization . '"',
					'no-such-entity',
					[ $badgeSerialization ]
				);
			}
		}
	}

}
