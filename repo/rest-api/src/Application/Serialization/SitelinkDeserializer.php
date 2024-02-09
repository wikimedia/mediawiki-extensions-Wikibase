<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkDeserializer {

	private string $invalidTitleRegex;
	private array $allowedBadgeItemIds;

	/**
	 * @param string $invalidTitleRegex
	 * @param string[] $allowedBadgeItemIds
	 */
	public function __construct( string $invalidTitleRegex, array $allowedBadgeItemIds ) {
		$this->invalidTitleRegex = $invalidTitleRegex;
		$this->allowedBadgeItemIds = $allowedBadgeItemIds;
	}

	public function deserialize( string $siteId, array $serialization ): SiteLink {
		if ( !array_key_exists( 'title', $serialization ) ) {
			throw new MissingFieldException( 'title' );
		}

		$trimmedTitle = trim( $serialization[ 'title' ] );
		if ( empty( $trimmedTitle ) ) {
			throw new EmptySitelinkException( 'title', $trimmedTitle );
		}
		if ( preg_match( $this->invalidTitleRegex, $trimmedTitle ) === 1 ) {
			throw new InvalidFieldException( 'title', $trimmedTitle );
		}

		$serialization[ 'badges' ] ??= [];
		if ( !is_array( $serialization[ 'badges' ] ) ) {
			throw new InvalidFieldTypeException( 'badges' );
		}

		$badges = [];
		foreach ( $serialization[ 'badges' ] as $badge ) {
			try {
				$badgeItemId = new ItemId( $badge );
			} catch ( InvalidArgumentException $e ) {
				throw new InvalidSitelinkBadgeException( $badge );
			}
			if ( !in_array( (string)$badgeItemId, $this->allowedBadgeItemIds ) ) {
				throw new BadgeNotAllowed( $badgeItemId );
			}
			$badges[] = $badgeItemId;
		}

		return new SiteLink( $siteId, $trimmedTitle, $badges );
	}

}
