<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks;

use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Application\Serialization\BadgeNotAllowed;
use Wikibase\Repo\RestApi\Application\Serialization\EmptySitelinkException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldTypeException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidSitelinkBadgeException;
use Wikibase\Repo\RestApi\Application\Serialization\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Domain\Services\Exceptions\SitelinkTargetNotFound;

/**
 * @license GPL-2.0-or-later
 */
class PatchedSitelinksValidator {

	private SiteIdValidator $siteIdValidator;
	private SitelinkDeserializer $sitelinkDeserializer;

	public function __construct( SiteIdValidator $siteIdValidator, SitelinkDeserializer $sitelinkDeserializer ) {
		$this->siteIdValidator = $siteIdValidator;
		$this->sitelinkDeserializer = $sitelinkDeserializer;
	}

	public function validateAndDeserialize( array $serialization ): SiteLinkList {
		$this->validateSiteIds( array_keys( $serialization ) );
		return $this->deserialize( $serialization );
	}

	private function deserialize( array $serialization ): SiteLinkList {
		$sitelinks = [];

		foreach ( $serialization as $siteId => $sitelink ) {
			try {
				$sitelinks[ $siteId ] = $this->sitelinkDeserializer->deserialize( $siteId, $sitelink );
			} catch ( MissingFieldException $e ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_MISSING_TITLE,
					"No sitelink title provided for site '$siteId' in patched sitelinks",
					[ UseCaseError::CONTEXT_SITE_ID => $siteId ]
				);
			} catch ( EmptySitelinkException $e ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_TITLE_EMPTY,
					"Sitelink cannot be empty for site '$siteId' in patched sitelinks",
					[ UseCaseError::CONTEXT_SITE_ID => $siteId ]
				);
			} catch ( InvalidFieldException $e ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_INVALID_TITLE,
					"Invalid sitelink title '{$e->getValue()}' for site '$siteId' in patched sitelinks",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId,
						UseCaseError::CONTEXT_TITLE => $e->getValue(),
					]
				);
			} catch ( InvalidFieldTypeException $e ) {
				switch ( $e->getField() ) {
					case 'title':
						throw new UseCaseError(
							UseCaseError::PATCHED_SITELINK_INVALID_TITLE,
							"Invalid sitelink title '{$sitelink[ 'title' ]}' for site '$siteId' in patched sitelinks",
							[
								UseCaseError::CONTEXT_SITE_ID => $siteId,
								UseCaseError::CONTEXT_TITLE => $sitelink[ 'title' ],
							]
						);
					case 'badges':
						throw new UseCaseError(
							UseCaseError::PATCHED_SITELINK_BADGES_FORMAT,
							"Badges value for site '$siteId' is not a list in patched sitelinks",
							[
								UseCaseError::CONTEXT_SITE_ID => $siteId,
								UseCaseError::CONTEXT_BADGES => $sitelink[ 'badges' ],
							]
						);
				}
			} catch ( InvalidSitelinkBadgeException $e ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_INVALID_BADGE,
					"Incorrect patched sitelinks. Badge value '{$e->getValue()}' for site '$siteId' is not an item ID",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId,
						UseCaseError::CONTEXT_BADGE => $e->getValue(),
					]
				);
			} catch ( BadgeNotAllowed $e ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_ITEM_NOT_A_BADGE,
					"Incorrect patched sitelinks. Item '{$e->getBadge()->getSerialization()}'" .
					" used for site '$siteId' is not allowed as a badge",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId,
						UseCaseError::CONTEXT_BADGE => $e->getBadge()->getSerialization(),
					]
				);
			} catch ( SitelinkTargetNotFound $e ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST,
					"Incorrect patched sitelinks. Page with title '{$sitelink[ 'title' ]}' does not exist on site '$siteId'",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId,
						UseCaseError::CONTEXT_TITLE => $sitelink[ 'title' ],
					]
				);
			}
		}

		return new SiteLinkList( $sitelinks );
	}

	private function validateSiteIds( array $siteIds ): void {
		foreach ( $siteIds as $siteId ) {
			if ( $this->siteIdValidator->validate( $siteId ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_INVALID_SITE_ID,
					"Not a valid site ID '$siteId' in patched sitelinks",
					[ UseCaseError::CONTEXT_SITE_ID => $siteId ]
				);
			}
		}
	}

}
