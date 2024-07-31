<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Domain\Services\PropertyWriteModelRetriever;

/**
 * @license GPL-2.0-or-later
 */
class PropertyLabelEditRequestValidatingDeserializer {

	private PropertyLabelValidator $validator;
	private PropertyWriteModelRetriever $propertyRetriever;

	public function __construct( PropertyLabelValidator $validator, PropertyWriteModelRetriever $propertyRetriever ) {
		$this->validator = $validator;
		$this->propertyRetriever = $propertyRetriever;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PropertyLabelEditRequest $request ): Term {
		$property = $this->propertyRetriever->getPropertyWriteModel( new NumericPropertyId( $request->getPropertyId() ) );
		$label = $request->getLabel();
		$language = $request->getLanguageCode();

		// skip if property does not exist or label is unchanged
		if ( !$property ||
			 ( $property->getLabels()->hasTermForLanguage( $language ) &&
			   $property->getLabels()->getByLanguage( $language )->getText() === $label
			 )
		) {
			return new Term( $language, $label );
		}

		$validationError = $this->validator->validate(
			$language,
			$label,
			$property->getDescriptions()
		);
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case PropertyLabelValidator::CODE_INVALID:
				case PropertyLabelValidator::CODE_EMPTY:
					throw UseCaseError::newInvalidValue( '/label' );
				case PropertyLabelValidator::CODE_TOO_LONG:
					throw UseCaseError::newValueTooLong( '/label', $context[PropertyLabelValidator::CONTEXT_LIMIT] );
				case PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL:
					$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
					throw new UseCaseError(
						UseCaseError::LABEL_DESCRIPTION_SAME_VALUE,
						"Label and description for language code '$language' can not have the same value.",
						[ UseCaseError::CONTEXT_LANGUAGE => $context[PropertyLabelValidator::CONTEXT_LANGUAGE] ]
					);
				case PropertyLabelValidator::CODE_LABEL_DUPLICATE:
					$language = $context[PropertyLabelValidator::CONTEXT_LANGUAGE];
					$conflictingPropertyId = $context[PropertyLabelValidator::CONTEXT_CONFLICTING_PROPERTY_ID];

					throw UseCaseError::newDataPolicyViolation(
						UseCaseError::POLICY_VIOLATION_PROPERTY_LABEL_DUPLICATE,
						[
							UseCaseError::CONTEXT_LANGUAGE => $language,
							UseCaseError::CONTEXT_CONFLICTING_PROPERTY_ID => $conflictingPropertyId,
						]
					);
				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}
		return new Term( $request->getLanguageCode(), $request->getLabel() );
	}

}
