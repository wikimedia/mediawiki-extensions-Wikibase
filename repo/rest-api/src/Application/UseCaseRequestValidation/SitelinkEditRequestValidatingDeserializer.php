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
		$validationError = $this->validator->validate( $request->getItemId(), $request->getSiteId(), $request->getSitelink() );

		if ( $validationError ) {
			switch ( $validationError->getCode() ) {
				case SitelinkValidator::CODE_TITLE_MISSING:
					throw UseCaseError::newMissingField( '/sitelink', 'title' );
				case SitelinkValidator::CODE_EMPTY_TITLE:
				case SitelinkValidator::CODE_INVALID_TITLE:
				case SitelinkValidator::CODE_INVALID_TITLE_TYPE:
					throw UseCaseError::newInvalidValue( '/sitelink/title' );
				case SitelinkValidator::CODE_INVALID_BADGES_TYPE:
					throw UseCaseError::newInvalidValue( '/sitelink/badges' );
				case SitelinkValidator::CODE_INVALID_BADGE:
					$badge = $validationError->getContext()[SitelinkValidator::CONTEXT_BADGE];
					$badgeIndex = Utils::getIndexOfValueInSerialization( $badge, $request->getSitelink()[ 'badges' ] );
					throw UseCaseError::newInvalidValue( "/sitelink/badges/$badgeIndex" );
				case SitelinkValidator::CODE_BADGE_NOT_ALLOWED:
					$badge = (string)$validationError->getContext()[ SitelinkValidator::CONTEXT_BADGE ];
					throw new UseCaseError(
						UseCaseError::ITEM_NOT_A_BADGE,
						"Item ID provided as badge is not allowed as a badge: $badge",
						[ UseCaseError::CONTEXT_BADGE => $badge ]
					);
				case SitelinkValidator::CODE_TITLE_NOT_FOUND:
					throw new UseCaseError(
						UseCaseError::SITELINK_TITLE_NOT_FOUND,
						"Page with title {$request->getSitelink()['title']} does not exist on the given site"
					);
				case SitelinkValidator::CODE_SITELINK_CONFLICT:
					$conflictItemId = (string)$validationError->getContext()[ SitelinkValidator::CONTEXT_CONFLICT_ITEM_ID ];
					throw new UseCaseError(
						UseCaseError::SITELINK_CONFLICT,
						"Sitelink is already being used on $conflictItemId",
						[ UseCaseError::CONTEXT_MATCHING_ITEM_ID => $conflictItemId ]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->validator->getValidatedSitelink();
	}

}
