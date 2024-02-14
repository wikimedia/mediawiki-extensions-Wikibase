<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkValidator {

	public const CODE_TITLE_MISSING = 'title-missing';
	public const CODE_EMPTY_TITLE = 'empty-title';
	public const CODE_INVALID_TITLE = 'invalid-title';

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
			switch ( $e->getField() ) {
				case 'title':
					return new ValidationError( self::CODE_INVALID_TITLE );
				default:
					throw new LogicException( "Unknown field '{$e->getField()}' in InvalidFieldException}" );
			}
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
