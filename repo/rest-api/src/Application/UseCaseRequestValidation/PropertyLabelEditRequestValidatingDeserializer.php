<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyLabelEditRequestValidatingDeserializer {

	private PropertyLabelValidator $validator;

	public function __construct( PropertyLabelValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PropertyLabelEditRequest $request ): Term {
		$validationError = $this->validator->validate(
			new NumericPropertyId( $request->getPropertyId() ),
			$request->getLanguageCode(),
			$request->getLabel()
		);
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case PropertyLabelValidator::CODE_INVALID:
					throw new UseCaseError(
						UseCaseError::INVALID_LABEL,
						"Not a valid label: {$context[PropertyLabelValidator::CONTEXT_LABEL]}"
					);
				case PropertyLabelValidator::CODE_EMPTY:
					throw new UseCaseError( UseCaseError::LABEL_EMPTY, 'Label must not be empty' );
				case PropertyLabelValidator::CODE_TOO_LONG:
					$maxLabelLength = $context[PropertyLabelValidator::CONTEXT_LIMIT];
					throw new UseCaseError(
						UseCaseError::LABEL_TOO_LONG,
						"Label must be no more than $maxLabelLength characters long",
						[
							UseCaseError::CONTEXT_VALUE => $context[PropertyLabelValidator::CONTEXT_LABEL],
							UseCaseError::CONTEXT_CHARACTER_LIMIT => $maxLabelLength,
						]
					);
				case PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
					$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language code '$language' can not have the same value.",
						[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyLabelValidator::CONTEXT_LANGUAGE] ]
					);
				case PropertyLabelValidator::CODE_LABEL_DUPLICATE:
					$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
					$matchingPropertyId = $context[PropertyLabelValidator::CONTEXT_MATCHING_PROPERTY_ID];
					$label = $context[PropertyLabelValidator::CONTEXT_LABEL];
					throw new UseCaseError(
						UseCaseError::PROPERTY_LABEL_DUPLICATE,
						"Property $matchingPropertyId already has label '$label' associated with " .
						"language code '$language'",
						[
							UseCaseError::CONTEXT_LANGUAGE => $language,
							UseCaseError::CONTEXT_LABEL => $label,
							UseCaseError::CONTEXT_MATCHING_PROPERTY_ID => $matchingPropertyId,
						]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}
		return new Term( $request->getLanguageCode(), $request->getLabel() );
	}

}
