<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\ChangeOp\ChangeOpValidationException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\TermDuplicateDetector;

/**
 * Encapsulates validation of terms (labels, descriptions, and aliases)
 * in the context of ChangeOps.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermChangeValidationHelper {
//FIXME: find a better name. TermChangeValidator isn't quite right though

	/**
	 * @var TermDuplicateDetector
	 */
	private $termUniquenessValidator;

	/**
	 * @var ValueValidator
	 */
	protected $languageValidator;

	/**
	 * @var ValueValidator
	 */
	protected $labelValidator;

	/**
	 * @var ValueValidator
	 */
	protected $descriptionValidator;

	/**
	 * @var ValueValidator
	 */
	protected $aliasValidator;

	/**
	 * @param ValueValidator $languageValidator
	 * @param ValueValidator $labelValidator
	 * @param ValueValidator $descriptionValidator
	 * @param ValueValidator $aliasValidator
	 * @param TermDuplicateDetector $termUniquenessValidator
	 */
	function __construct(
		ValueValidator $languageValidator,
		ValueValidator $labelValidator,
		ValueValidator $descriptionValidator,
		ValueValidator $aliasValidator,
		TermDuplicateDetector $termUniquenessValidator
	) {
		$this->aliasValidator = $aliasValidator;
		$this->descriptionValidator = $descriptionValidator;
		$this->labelValidator = $labelValidator;
		$this->languageValidator = $languageValidator;
		$this->termUniquenessValidator = $termUniquenessValidator;
	}

	/**
	 * Validates a language code.
	 *
	 * @param string $language
	 * @throws ChangeOpValidationException If the validation failed.
	 */
	public function validateLanguage( $language ) {
		$this->handleResult( $this->languageValidator->validate( $language ) );
	}

	/**
	 * Validates a label.
	 *
	 * @param string $language
	 * @param string $label
	 * @throws ChangeOpValidationException If the validation failed.
	 */
	public function validateLabel( $language, $label ) {
		$this->handleResult( $this->labelValidator->validate( $label ) );
	}

	/**
	 * Validates a description.
	 *
	 * @param string $language
	 * @param string $description
	 * @throws ChangeOpValidationException If the validation failed.
	 */
	public function validateDescription( $language, $description ) {
		$this->handleResult( $this->descriptionValidator->validate( $description ) );
	}

	/**
	 * Validates an alias.
	 *
	 * @param string $language
	 * @param string $alias
	 * @throws ChangeOpValidationException If the validation failed.
	 */
	public function validateAlias( $language, $alias ) {
		$this->handleResult( $this->aliasValidator->validate( $alias ) );
	}

	/**
	 * Validates an uniqueness constraints on the given combination of label and description.
	 *
	 * @param EntityId $entityId
	 * @param string $language
	 * @param string|null $label
	 * @param string|null $description
	 *
	 * @throws ChangeOpValidationException If the validation failed.
	 */
	public function validateUniqueness( EntityId $entityId, $language, $label, $description ) {
		$fields = array();

		if ( $label !== null ) {
			$fields['label'] = $label;
		}

		if ( $description !== null ) {
			$fields['description'] = $description;
		}

		if ( empty( $fields ) ) {
			return; // nothing to do
		}

		$terms = array( $language => $fields );
		$this->validateUniquenessForBatch( $entityId, $terms );
	}

	/**
	 * Validates the uniqueness constraints on the combination of label and description given
	 * for all the languages in $terms.
	 *
	 * @param EntityId $entityId
	 * @param array $terms An associative array mapping language codes to
	 *        records. Reach record is an associative array with they keys "label" and
	 *        "description", providing a label and description for each language.
	 *        Both the label and the description for a language may be null.
	 *
	 * @throws ChangeOpValidationException If the validation failed.
	 */
	public function validateUniquenessForBatch( EntityId $entityId, $terms ) {
		if ( empty( $terms ) ) {
			return; // nothing to do
		}

		$result = $this->termUniquenessValidator->detectTermDuplicates( $entityId, $terms );
		$this->handleResult( $result );
	}

	/**
	 * Throws an ChangeOpValidationException if $result->isValid() returns false.
	 *
	 * @param Result $result
	 *
	 * @throws ChangeOpValidationException
	 */
	protected function handleResult( Result $result ) {
		if ( !$result->isValid() ) {
			throw new ChangeOpValidationException( $result );
		}
	}
}