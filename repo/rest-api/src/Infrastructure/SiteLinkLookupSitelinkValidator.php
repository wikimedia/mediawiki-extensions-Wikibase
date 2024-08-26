<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\BadgeNotAllowed;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidSitelinkBadgeException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;

/**
 * @license GPL-2.0-or-later
 */
class SiteLinkLookupSitelinkValidator implements SitelinkValidator {

	private SitelinkDeserializer $sitelinkDeserializer;
	private SiteLinkLookup $siteLinkLookup;

	private ?SiteLink $deserializedSitelink = null;

	public function __construct( SitelinkDeserializer $sitelinkDeserializer, SiteLinkLookup $siteLinkLookup ) {
		$this->sitelinkDeserializer = $sitelinkDeserializer;
		$this->siteLinkLookup = $siteLinkLookup;
	}

	public function validate(
		?string $itemId,
		string $siteId,
		array $sitelink,
		string $basePath = ''
	): ?ValidationError {
		try {
			$this->deserializedSitelink = $this->sitelinkDeserializer->deserialize( $siteId, $sitelink, $basePath );
		} catch ( MissingFieldException $e ) {
			return new ValidationError( self::CODE_TITLE_MISSING, [ self::CONTEXT_PATH => $e->getPath() ] );
		} catch ( EmptySitelinkException $e ) {
			return new ValidationError(
				self::CODE_EMPTY_TITLE,
				[ self::CONTEXT_PATH => $e->getPath(), self::CONTEXT_VALUE => $e->getValue() ]
			);
		} catch ( InvalidFieldException $e ) {
			if ( $e->getField() !== 'title' ) {
				throw new LogicException( "Unknown field '{$e->getField()}' in InvalidFieldException}" );
			}
			return new ValidationError(
				self::CODE_INVALID_TITLE,
				[ self::CONTEXT_PATH => $e->getPath(), self::CONTEXT_VALUE => $e->getValue() ]
			);
		} catch ( InvalidFieldTypeException $e ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD_TYPE,
				[ self::CONTEXT_PATH => $e->getPath(), self::CONTEXT_VALUE => $e->getValue() ]
			);
		} catch ( InvalidSitelinkBadgeException $e ) {
			return new ValidationError(
				self::CODE_INVALID_BADGE,
				[ self::CONTEXT_VALUE => $e->getValue(), self::CONTEXT_SITE_ID => $siteId ]
			);
		} catch ( BadgeNotAllowed $e ) {
			return new ValidationError(
				self::CODE_BADGE_NOT_ALLOWED,
				[ self::CONTEXT_VALUE => $e->getBadge(), self::CONTEXT_SITE_ID => $siteId ]
			);
		} catch ( SitelinkTargetNotFound $e ) {
			return new ValidationError( self::CODE_TITLE_NOT_FOUND, [ self::CONTEXT_SITE_ID => $siteId ] );
		}

		return $this->checkSitelinkConflict( $itemId, $this->deserializedSitelink );
	}

	public function getValidatedSitelink(): SiteLink {
		if ( $this->deserializedSitelink === null ) {
			throw new LogicException( 'getValidatedSitelink() called before validate()' );
		}

		return $this->deserializedSitelink;
	}

	private function checkSitelinkConflict( ?string $itemId, SiteLink $siteLink ): ?ValidationError {
		$existingItemWithSitelink = $this->siteLinkLookup->getItemIdForSiteLink( $siteLink );

		return $existingItemWithSitelink && ( !$itemId || !( new ItemId( $itemId ) )->equals( $existingItemWithSitelink ) )
			? new ValidationError(
				self::CODE_SITELINK_CONFLICT,
				[
					self::CONTEXT_CONFLICTING_ITEM_ID => $existingItemWithSitelink->getSerialization(),
					self::CONTEXT_SITE_ID => $siteLink->getSiteId(),
				]
			)
			: null;
	}

}
