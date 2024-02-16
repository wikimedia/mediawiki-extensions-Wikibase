<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\BadgeNotAllowed;
use Wikibase\Repo\RestApi\Application\Serialization\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidSitelinkBadgeException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkValidator {

	public const CODE_TITLE_MISSING = 'title-missing';
	public const CODE_EMPTY_TITLE = 'empty-title';
	public const CODE_INVALID_TITLE = 'invalid-title';
	public const CODE_INVALID_TITLE_TYPE = 'invalid-title-type';

	public const CODE_INVALID_BADGES_TYPE = 'invalid-badges-type';
	public const CODE_INVALID_BADGE = 'invalid-badge';
	public const CODE_BADGE_NOT_ALLOWED = 'badge-not-allowed';

	public const CONTEXT_BADGE = 'badge';

	private SitelinkDeserializer $sitelinkDeserializer;

	private ?SiteLink $deserializedSitelink = null;

	public function __construct( SitelinkDeserializer $sitelinkDeserializer ) {
		$this->sitelinkDeserializer = $sitelinkDeserializer;
	}

	public function validate( string $siteId, array $sitelink ): ?ValidationError {
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
		}

		return null;
	}

	public function getValidatedSitelink(): SiteLink {
		if ( $this->deserializedSitelink === null ) {
			throw new LogicException( 'getValidatedSitelink() called before validate()' );
		}

		return $this->deserializedSitelink;
	}

}
