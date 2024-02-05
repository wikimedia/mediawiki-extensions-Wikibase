<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\SiteLink;
use Wikibase\Repo\RestApi\Application\Serialization\SitelinkDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\SitelinkValidator;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkEditRequestValidatingDeserializer {

	private SitelinkValidator $validator;
	private SitelinkDeserializer $sitelinkDeserializer;

	public function __construct( SitelinkValidator $validator, SitelinkDeserializer $sitelinkDeserializer ) {
		$this->validator = $validator;
		$this->sitelinkDeserializer = $sitelinkDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( SitelinkEditRequest $request ): SiteLink {
		$validationError = $this->validator->validate( $request->getSitelink() );
		if ( $validationError ) {
			switch ( $validationError->getCode() ) {
				case SitelinkValidator::CODE_TITLE_MISSING:
					throw new UseCaseError(
						UseCaseError::SITELINK_DATA_MISSING_TITLE,
						'Mandatory sitelink title missing',
					);
				case SitelinkValidator::CODE_EMPTY_TITLE:
					throw new UseCaseError(
						UseCaseError::TITLE_FIELD_EMPTY,
						'Title must not be empty',
					);
				case SitelinkValidator::CODE_INVALID_TITLE:
					throw new UseCaseError(
						UseCaseError::INVALID_TITLE_FIELD,
						'Not a valid input for title field',
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $this->sitelinkDeserializer->deserialize( $request->getSiteId(), $request->getSitelink() );
	}
}
