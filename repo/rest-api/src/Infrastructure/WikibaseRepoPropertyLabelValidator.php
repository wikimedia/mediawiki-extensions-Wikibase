<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoPropertyLabelValidator implements PropertyLabelValidator {

	private TermValidatorFactory $termValidatorFactory;
	private TermsCollisionDetector $termsCollisionDetector;
	private PropertyRetriever $propertyRetriever;

	public function __construct(
		TermValidatorFactory $termValidatorFactory,
		TermsCollisionDetector $termsCollisionDetector,
		PropertyRetriever $propertyRetriever
	) {
		$this->termValidatorFactory = $termValidatorFactory;
		$this->termsCollisionDetector = $termsCollisionDetector;
		$this->propertyRetriever = $propertyRetriever;
	}

	public function validate( PropertyId $propertyId, string $language, string $label ): ?ValidationError {
		return $this->validateLabel( $label )
			   ?? $this->validateProperty( $propertyId, $language, $label );
	}

	public function validateLabel( string $labelText ): ?ValidationError {
		$result = $this->termValidatorFactory
			->getLabelValidator( Property::ENTITY_TYPE )
			->validate( $labelText );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[0];
			switch ( $error->getCode() ) {
				case 'label-too-short':
					return new ValidationError( PropertyLabelValidator::CODE_EMPTY );
				case 'label-too-long':
					return new ValidationError(
						PropertyLabelValidator::CODE_TOO_LONG,
						[
							PropertyLabelValidator::CONTEXT_LABEL => $labelText,
							PropertyLabelValidator::CONTEXT_LIMIT => $error->getParameters()[0],
						]
					);
				default:
					return new ValidationError(
						PropertyLabelValidator::CODE_INVALID,
						[ PropertyLabelValidator::CONTEXT_LABEL => $labelText ]
					);
			}
		}

		return null;
	}

	private function validateProperty( PropertyId $propertyId, string $language, string $label ): ?ValidationError {
		$property = $this->propertyRetriever->getProperty( $propertyId );

		// skip if Property does not exist
		if ( $property === null ) {
			return null;
		}

		// skip if label is unchanged
		if ( $property->getLabels()->hasTermForLanguage( $language ) &&
			 $property->getLabels()->getByLanguage( $language )->getText() === $label
		) {
			return null;
		}

		$entityId = $this->termsCollisionDetector->detectLabelCollision( $language, $label );
		if ( $entityId instanceof PropertyId ) {
			return new ValidationError(
				self::CODE_LABEL_DUPLICATE,
				[
					self::CONTEXT_LANGUAGE => $language,
					self::CONTEXT_LABEL => $label,
					self::CONTEXT_MATCHING_PROPERTY_ID => (string)$entityId,
				]
			);
		}

		// skip if Property does not have a description
		if ( !$property->getDescriptions()->hasTermForLanguage( $language ) ) {
			return null;
		}

		$description = $property->getDescriptions()->getByLanguage( $language )->getText();
		if ( $label === $description ) {
			return new ValidationError(
				PropertyLabelValidator::CODE_LABEL_DESCRIPTION_EQUAL,
				[ PropertyLabelValidator::CONTEXT_LANGUAGE => $language ],
			);
		}

		return null;
	}
}
