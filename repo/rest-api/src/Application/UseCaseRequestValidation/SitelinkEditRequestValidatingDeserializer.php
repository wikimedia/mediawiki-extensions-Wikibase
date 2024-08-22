<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkEditRequestValidatingDeserializer {

	private SitelinkValidator $validator;

	public function __construct( SitelinkValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( SitelinkEditRequest $request ): SiteLink {
		$validationError = $this->validator->validate( $request->getItemId(), $request->getSiteId(), $request->getSitelink(), '/sitelink' );

		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case SitelinkValidator::CODE_TITLE_MISSING:
					throw UseCaseError::newMissingField( '/sitelink', 'title' );
				case SitelinkValidator::CODE_EMPTY_TITLE:
					throw UseCaseError::newInvalidValue( '/sitelink/title' );
				case SitelinkValidator::CODE_INVALID_TITLE:
				case SitelinkValidator::CODE_INVALID_FIELD_TYPE:
					throw UseCaseError::newInvalidValue( $context[SitelinkValidator::CONTEXT_PATH] );
				case SitelinkValidator::CODE_INVALID_BADGE:
				case SitelinkValidator::CODE_BADGE_NOT_ALLOWED:
					$badge = $context[SitelinkValidator::CONTEXT_VALUE];
					$badgeIndex = Utils::getIndexOfValueInSerialization( $badge, $request->getSitelink()[ 'badges' ] );
					throw UseCaseError::newInvalidValue( "/sitelink/badges/$badgeIndex" );
				case SitelinkValidator::CODE_TITLE_NOT_FOUND:
					throw new UseCaseError(
						UseCaseError::SITELINK_TITLE_NOT_FOUND,
						"Page with title {$request->getSitelink()['title']} does not exist on the given site"
					);
				case SitelinkValidator::CODE_SITELINK_CONFLICT:
					$conflictingItemId = (string)$validationError->getContext()[ SitelinkValidator::CONTEXT_CONFLICTING_ITEM_ID ];
					throw UseCaseError::newDataPolicyViolation(
						UseCaseError::POLICY_VIOLATION_SITELINK_CONFLICT,
						[ UseCaseError::CONTEXT_CONFLICTING_ITEM_ID => $conflictingItemId ]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->validator->getValidatedSitelink();
	}

}
