<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Application\Serialization\BadgeNotAllowed;
use Wikibase\Repo\RestApi\Application\Serialization\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidSitelinkBadgeException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;
use Wikibase\Repo\Store\SiteLinkConflictLookup;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkConflictLookupSitelinkValidator implements SitelinkValidator {

	private SitelinkDeserializer $sitelinkDeserializer;
	private SiteLinkConflictLookup $siteLinkConflictLookup;

	private ?SiteLink $deserializedSitelink = null;

	public function __construct( SitelinkDeserializer $sitelinkDeserializer, SiteLinkConflictLookup $siteLinkConflictLookup ) {
		$this->sitelinkDeserializer = $sitelinkDeserializer;
		$this->siteLinkConflictLookup = $siteLinkConflictLookup;
	}

	public function validate( string $itemId, string $siteId, array $sitelink ): ?ValidationError {
		try {
			$this->deserializedSitelink = $this->sitelinkDeserializer->deserialize( $siteId, $sitelink );
		} catch ( MissingFieldException $e ) {
			return new ValidationError( self::CODE_TITLE_MISSING );
		} catch ( EmptySitelinkException $e ) {
			return new ValidationError( self::CODE_EMPTY_TITLE );
		} catch ( InvalidFieldException $e ) {
			if ( $e->getField() !== 'title' ) {
				throw new LogicException( "Unknown field '{$e->getField()}' in InvalidFieldException}" );
			}
			return new ValidationError( self::CODE_INVALID_TITLE );
		} catch ( InvalidFieldTypeException $e ) {
			switch ( $e->getField() ) {
				case 'title':
					return new ValidationError( self::CODE_INVALID_TITLE_TYPE );
				case 'badges':
					return new ValidationError( self::CODE_INVALID_BADGES_TYPE );
				default:
					throw new LogicException( "Unknown field '{$e->getField()}' in InvalidFieldTypeException}" );
			}
		} catch ( InvalidSitelinkBadgeException $e ) {
			return new ValidationError( self::CODE_INVALID_BADGE, [ self::CONTEXT_BADGE => $e->getValue() ] );
		} catch ( BadgeNotAllowed $e ) {
			return new ValidationError( self::CODE_BADGE_NOT_ALLOWED, [ self::CONTEXT_BADGE => $e->getBadge() ] );
		} catch ( SitelinkTargetNotFound $e ) {
			return new ValidationError( self::CODE_TITLE_NOT_FOUND );
		}

		return $this->checkSitelinkConflict( $itemId, $siteId, $sitelink );
	}

	public function getValidatedSitelink(): SiteLink {
		if ( $this->deserializedSitelink === null ) {
			throw new LogicException( 'getValidatedSitelink() called before validate()' );
		}

		return $this->deserializedSitelink;
	}

	private function checkSitelinkConflict( string $itemId, string $siteId, array $sitelink ): ?ValidationError {
		$item = new Item(
			new ItemId( $itemId ),
			null,
			new SiteLinkList( [ new SiteLink( $siteId, $sitelink[ 'title' ] ) ] )
		);

		$sitelinkConflictArray = $this->siteLinkConflictLookup->getConflictsForItem( $item );

		if ( is_array( $sitelinkConflictArray ) && count( $sitelinkConflictArray ) > 0 ) {
			return new ValidationError(
				self::CODE_SITELINK_CONFLICT,
				[ self::CONTEXT_CONFLICT_ITEM_ID => $sitelinkConflictArray[0]['itemId'] ]
			);
		}

		return null;
	}

}
