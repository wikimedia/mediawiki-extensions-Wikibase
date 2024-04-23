<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\SiteLinkList;

/**
 * @license GPL-2.0-or-later
 */
class SitelinksValidator {

	public const CODE_INVALID_SITELINK = 'invalid-sitelink';
	public const CODE_SITELINKS_NOT_ASSOCIATIVE = 'invalid-sitelinks';

	public const CONTEXT_SITE_ID = 'site-id';

	private SiteIdValidator $siteIdValidator;
	private SitelinkValidator $sitelinkValidator;
	private ?SitelinkList $deserializedSitelinks = null;

	public function __construct( SiteIdValidator $siteIdValidator, SitelinkValidator $sitelinkValidator ) {
		$this->siteIdValidator = $siteIdValidator;
		$this->sitelinkValidator = $sitelinkValidator;
	}

	/**
	 * @param string|null $itemId - null if validating a new item
	 */
	public function validate( ?string $itemId, array $serialization ): ?ValidationError {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			return new ValidationError( self::CODE_SITELINKS_NOT_ASSOCIATIVE );
		}

		return $this->validateSiteIds( array_keys( $serialization ) )
			?: $this->validateSitelinks( $itemId, $serialization );
	}

	public function getValidatedSitelinks(): SiteLinkList {
		if ( $this->deserializedSitelinks === null ) {
			throw new LogicException( 'getValidatedSitelinks() called before validate()' );
		}

		return $this->deserializedSitelinks;
	}

	private function validateSiteIds( array $siteIds ): ?ValidationError {
		return array_reduce(
			$siteIds,
			fn( ?ValidationError $error, $siteId ) => $error ?: $this->siteIdValidator->validate( (string)$siteId )
		);
	}

	private function validateSitelinks( ?string $itemId, array $serialization ): ?ValidationError {
		$sitelinks = [];

		foreach ( $serialization as $siteId => $sitelink ) {
			if ( !is_array( $sitelink ) ) {
				return new ValidationError(
					self::CODE_INVALID_SITELINK,
					[ self::CONTEXT_SITE_ID => $siteId ]
				);
			}

			$validationError = $this->sitelinkValidator->validate( $itemId, $siteId, $sitelink );
			if ( $validationError ) {
				return $validationError;
			}
			$sitelinks[] = $this->sitelinkValidator->getValidatedSitelink();
		}

		$this->deserializedSitelinks = new SiteLinkList( $sitelinks );

		return null;
	}

}
