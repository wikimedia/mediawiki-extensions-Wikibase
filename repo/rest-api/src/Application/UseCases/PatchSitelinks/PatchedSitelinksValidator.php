<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks;

use LogicException;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\Utils;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SiteIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinksValidator;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedSitelinksValidator {

	private SitelinksValidator $sitelinksValidator;

	public function __construct( SitelinksValidator $sitelinksValidator ) {
		$this->sitelinksValidator = $sitelinksValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( string $itemId, array $originalSitelinks, array $serialization ): SiteLinkList {
		$this->assertValidSitelinks( $itemId, $originalSitelinks, $serialization );
		$this->assertUrlsNotModified( $originalSitelinks, $serialization );
		return $this->sitelinksValidator->getValidatedSitelinks();
	}

	private function assertValidSitelinks( string $itemId, array $originalSitelinks, array $serialization ): void {
		$validationError = $this->sitelinksValidator->validate(
			$itemId,
			$serialization,
			$this->getModifiedSitelinksSites( $originalSitelinks, $serialization )
		);
		if ( !$validationError ) {
			return;
		}

		$context = $validationError->getContext();
		$siteId = fn() => $context[SitelinkValidator::CONTEXT_SITE_ID];
		switch ( $validationError->getCode() ) {
			case SiteIdValidator::CODE_INVALID_SITE_ID:
				throw UseCaseError::newPatchResultInvalidKey( '', $context[SiteIdValidator::CONTEXT_SITE_ID_VALUE] );
			case SitelinkValidator::CODE_TITLE_MISSING:
				throw UseCaseError::newMissingFieldInPatchResult( "/{$siteId()}", 'title' );

			case SitelinkValidator::CODE_EMPTY_TITLE:
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_TITLE_EMPTY,
					"Sitelink cannot be empty for site '{$siteId()}' in patched sitelinks",
					[ UseCaseError::CONTEXT_SITE_ID => $siteId() ]
				);

			case SitelinkValidator::CODE_INVALID_TITLE:
			case SitelinkValidator::CODE_INVALID_TITLE_TYPE:
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_INVALID_TITLE,
					"Invalid sitelink title '{$serialization[$siteId()][ 'title' ]}' for site '{$siteId()}' in patched sitelinks",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_TITLE => $serialization[$siteId()][ 'title' ],
					]
				);

			case SitelinkValidator::CODE_INVALID_BADGES_TYPE:
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_BADGES_FORMAT,
					"Badges value for site '{$siteId()}' is not a list in patched sitelinks",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_BADGES => $serialization[$siteId()][ 'badges' ],
					]
				);

			case SitelinkValidator::CODE_INVALID_BADGE:
			case SitelinkValidator::CODE_BADGE_NOT_ALLOWED:
				$badge = (string)$context[ SitelinkValidator::CONTEXT_BADGE ];
				$badgeIndex = Utils::getIndexOfValueInSerialization( $badge, $serialization[$siteId()]['badges'] );
				throw UseCaseError::newPatchResultInvalidValue( "/{$siteId()}/badges/$badgeIndex", $badge );

			case SitelinkValidator::CODE_TITLE_NOT_FOUND:
				$title = $serialization[$siteId()]['title'];
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST,
					"Incorrect patched sitelinks. Page with title '$title' does not exist on site '{$siteId()}'",
					[
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
						UseCaseError::CONTEXT_TITLE => $serialization[$siteId()][ 'title' ],
					]
				);

			case SitelinkValidator::CODE_SITELINK_CONFLICT:
				$conflictingItemId = $context[ SitelinkValidator::CONTEXT_CONFLICTING_ITEM_ID ];
				throw UseCaseError::newDataPolicyViolation(
					UseCaseError::POLICY_VIOLATION_SITELINK_CONFLICT,
					[
						UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => "$conflictingItemId",
						UseCaseError::CONTEXT_SITE_ID => $siteId(),
					]
				);

			default:
				throw new LogicException( "Unknown validation error: {$validationError->getCode()}" );
		}
	}

	private function getModifiedSitelinksSites( array $originalSitelinks, array $patchedSitelinks ): array {
		return array_filter(
			array_keys( $patchedSitelinks ),
			fn( $siteId ) => !isset( $originalSitelinks[$siteId] )
				|| ( $patchedSitelinks[$siteId]['title'] ?? '' ) !== $originalSitelinks[$siteId]['title']
				|| ( $patchedSitelinks[$siteId]['badges'] ?? [] ) !== $originalSitelinks[$siteId]['badges']
		);
	}

	private function assertUrlsNotModified( array $originalSitelinksSerialization, array $patchedSitelinkSerialization ): void {
		foreach ( $patchedSitelinkSerialization as $siteId => $sitelink ) {
			if (
				isset( $sitelink[ 'url' ] ) &&
				isset( $originalSitelinksSerialization[ $siteId ] ) &&
				$originalSitelinksSerialization[ $siteId ][ 'url' ] !== $sitelink[ 'url' ]
			) {
				throw new UseCaseError(
					UseCaseError::PATCHED_SITELINK_URL_NOT_MODIFIABLE,
					'URL of sitelink cannot be modified',
					[ UseCaseError::CONTEXT_SITE_ID => $siteId ]
				);
			}
		}
	}

}
