<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\BadgeNotAllowed;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidSitelinkBadgeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\RestApi\Domain\Services\ItemRevisionMetadataRetriever;
use Wikibase\Repo\RestApi\Domain\Services\SitelinkTargetTitleResolver;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkDeserializer {

	private string $invalidTitleRegex;
	private array $allowedBadgeItemIds;
	private SitelinkTargetTitleResolver $titleResolver;
	private ItemRevisionMetadataRetriever $revisionMetadataRetriever;

	/**
	 * @param string $invalidTitleRegex
	 * @param string[] $allowedBadgeItemIds
	 * @param SitelinkTargetTitleResolver $titleResolver
	 * @param ItemRevisionMetadataRetriever $revisionMetadataRetriever
	 */
	public function __construct(
		string $invalidTitleRegex,
		array $allowedBadgeItemIds,
		SitelinkTargetTitleResolver $titleResolver,
		ItemRevisionMetadataRetriever $revisionMetadataRetriever
	) {
		$this->invalidTitleRegex = $invalidTitleRegex;
		$this->allowedBadgeItemIds = $allowedBadgeItemIds;
		$this->titleResolver = $titleResolver;
		$this->revisionMetadataRetriever = $revisionMetadataRetriever;
	}

	/**
	 * @throws MissingFieldException
	 * @throws InvalidFieldTypeException
	 * @throws EmptySitelinkException
	 * @throws InvalidFieldException
	 * @throws InvalidSitelinkBadgeException
	 * @throws BadgeNotAllowed
	 * @throws SitelinkTargetNotFound
	 */
	public function deserialize( string $siteId, array $serialization, string $basePath = '' ): SiteLink {
		if ( !array_key_exists( 'title', $serialization ) ) {
			throw new MissingFieldException( 'title' );
		}

		if ( !is_string( $serialization[ 'title' ] ) ) {
			throw new InvalidFieldTypeException( $serialization[ 'title' ], "$basePath/title" );
		}

		$trimmedTitle = trim( $serialization[ 'title' ] );
		if ( $trimmedTitle === '' ) {
			throw new EmptySitelinkException( 'title', $trimmedTitle );
		}
		if ( preg_match( $this->invalidTitleRegex, $trimmedTitle ) === 1 ) {
			throw new InvalidFieldException( 'title', $trimmedTitle, "$basePath/title" );
		}

		$serialization[ 'badges' ] ??= [];
		if ( !is_array( $serialization[ 'badges' ] ) ) {
			throw new InvalidFieldTypeException( $serialization[ 'badges' ], "$basePath/badges" );
		}

		$badges = [];
		foreach ( $serialization[ 'badges' ] as $badge ) {
			try {
				$badgeItemId = new ItemId( $badge );
			} catch ( InvalidArgumentException $e ) {
				throw new InvalidSitelinkBadgeException( $badge );
			}
			if ( !in_array( (string)$badgeItemId, $this->allowedBadgeItemIds ) ||
				!$this->revisionMetadataRetriever->getLatestRevisionMetadata( $badgeItemId )->itemExists() ) {
				throw new BadgeNotAllowed( $badgeItemId );
			}
			$badges[] = $badgeItemId;
		}

		return new SiteLink(
			$siteId,
			$this->titleResolver->resolveTitle( $siteId, $trimmedTitle, $badges ),
			$badges
		);
	}

}
