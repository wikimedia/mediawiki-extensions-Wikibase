<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\Services\PropertyRetriever;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoPropertyDescriptionValidator implements PropertyDescriptionValidator {

	private TermValidatorFactory $termValidatorFactory;
	private PropertyRetriever $propertyRetriever;

	public function __construct(
		TermValidatorFactory $termValidatorFactory,
		PropertyRetriever $propertyRetriever
	) {
		$this->termValidatorFactory = $termValidatorFactory;
		$this->propertyRetriever = $propertyRetriever;
	}

	public function validate( PropertyId $propertyId, string $language, string $description ): ?ValidationError {
		return $this->validateDescription( $description )
			   ?? $this->validateProperty( $propertyId, $language, $description );
	}

	private function validateDescription( string $description ): ?ValidationError {
		$result = $this->termValidatorFactory
			->getDescriptionValidator()
			->validate( $description );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[0];
			switch ( $error->getCode() ) {
				case 'description-too-short':
					return new ValidationError( self::CODE_EMPTY );
				case 'description-too-long':
					return new ValidationError(
						self::CODE_TOO_LONG,
						[
							self::CONTEXT_DESCRIPTION => $description,
							self::CONTEXT_LIMIT => $error->getParameters()[0],
						]
					);
				default:
					return new ValidationError(
						self::CODE_INVALID,
						[ self::CONTEXT_DESCRIPTION => $description ]
					);
			}
		}

		return null;
	}

	private function validateProperty( PropertyId $propertyId, string $language, string $description ): ?ValidationError {
		$property = $this->propertyRetriever->getProperty( $propertyId );

		// skip if Property does not exist
		if ( $property === null ) {
			return null;
		}

		// skip if description is unchanged
		if ( $property->getDescriptions()->hasTermForLanguage( $language ) &&
			 $property->getDescriptions()->getByLanguage( $language )->getText() === $description
		) {
			return null;
		}

		// skip if Property does not have a label
		if ( !$property->getLabels()->hasTermForLanguage( $language ) ) {
			return null;
		}

		$label = $property->getLabels()->getByLanguage( $language )->getText();
		if ( $label === $description ) {
			return new ValidationError(
				self::CODE_LABEL_DESCRIPTION_EQUAL,
				[ self::CONTEXT_LANGUAGE => $language ]
			);
		}

		return null;
	}

}
