<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks;

use LogicException;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedSitelinksValidator {

	private SiteIdValidator $siteIdValidator;
	private SitelinkValidator $sitelinkValidator;

	public function __construct(
		SiteIdValidator $siteIdValidator,
		SitelinkValidator $sitelinkValidator
	) {
		$this->siteIdValidator = $siteIdValidator;
		$this->sitelinkValidator = $sitelinkValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( string $itemId, array $serialization ): SiteLinkList {
		$this->validateSiteIds( array_keys( $serialization ) );
		return $this->validateSitelink( $itemId, $serialization );
	}

	private function validateSitelink( string $itemId, array $serialization ): SiteLinkList {
		$sitelinks = [];

		foreach ( $serialization as $siteId => $sitelink ) {

			$validationError = $this->sitelinkValidator->validate( $itemId, $siteId, $sitelink );

			if ( !$validationError ) {
				$sitelinks[] = $this->sitelinkValidator->getValidatedSitelink();
				continue;
			}

			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case SitelinkValidator::CODE_TITLE_MISSING:
					throw new UseCaseError(
						UseCaseError::PATCHED_SITELINK_MISSING_TITLE,
						"No sitelink title provided for site '$siteId' in patched sitelinks",
						[ UseCaseError::CONTEXT_SITE_ID => $siteId ]
					);

				case SitelinkValidator::CODE_EMPTY_TITLE:
					throw new UseCaseError(
						UseCaseError::PATCHED_SITELINK_TITLE_EMPTY,
						"Sitelink cannot be empty for site '$siteId' in patched sitelinks",
						[ UseCaseError::CONTEXT_SITE_ID => $siteId ]
					);

				case SitelinkValidator::CODE_INVALID_TITLE:
				case SitelinkValidator::CODE_INVALID_TITLE_TYPE:
					throw new UseCaseError(
						UseCaseError::PATCHED_SITELINK_INVALID_TITLE,
						"Invalid sitelink title '{$sitelink[ 'title' ]}' for site '$siteId' in patched sitelinks",
						[
							UseCaseError::CONTEXT_SITE_ID => $siteId,
							UseCaseError::CONTEXT_TITLE => $sitelink[ 'title' ],
						]
					);

				case SitelinkValidator::CODE_INVALID_BADGES_TYPE:
					throw new UseCaseError(
						UseCaseError::PATCHED_SITELINK_BADGES_FORMAT,
						"Badges value for site '$siteId' is not a list in patched sitelinks",
						[
							UseCaseError::CONTEXT_SITE_ID => $siteId,
							UseCaseError::CONTEXT_BADGES => $sitelink[ 'badges' ],
						]
					);

				case SitelinkValidator::CODE_INVALID_BADGE:
					$badge = $context[ SitelinkValidator::CONTEXT_BADGE ];
					throw new UseCaseError(
						UseCaseError::PATCHED_SITELINK_INVALID_BADGE,
						"Incorrect patched sitelinks. Badge value '$badge' for site '$siteId' is not an item ID",
						[
							UseCaseError::CONTEXT_SITE_ID => $siteId,
							UseCaseError::CONTEXT_BADGE => $badge,
						]
					);

				case SitelinkValidator::CODE_BADGE_NOT_ALLOWED:
					$badge = (string)$context[ SitelinkValidator::CONTEXT_BADGE ];
					throw new UseCaseError(
						UseCaseError::PATCHED_SITELINK_ITEM_NOT_A_BADGE,
						"Incorrect patched sitelinks. Item '$badge' used for site '$siteId' is not allowed as a badge",
						[
							UseCaseError::CONTEXT_SITE_ID => $siteId,
							UseCaseError::CONTEXT_BADGE => $badge,
						]
					);

				case SitelinkValidator::CODE_TITLE_NOT_FOUND:
					throw new UseCaseError(
						UseCaseError::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST,
						"Incorrect patched sitelinks. Page with title '{$sitelink[ 'title' ]}' does not exist on site '$siteId'",
						[
							UseCaseError::CONTEXT_SITE_ID => $siteId,
							UseCaseError::CONTEXT_TITLE => $sitelink[ 'title' ],
						]
					);

				case SitelinkValidator::CODE_SITELINK_CONFLICT:
					$matchingItemId = $context[ SitelinkValidator::CONTEXT_CONFLICT_ITEM_ID ];
					throw new UseCaseError(
						UseCaseError::PATCHED_SITELINK_CONFLICT,
						"Site '$siteId' is already being used on '$matchingItemId'",
						[
							UseCaseError::CONTEXT_MATCHING_ITEM_ID => "$matchingItemId",
							UseCaseError::CONTEXT_SITE_ID => $siteId,
						]
					);

				default:
					throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
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
