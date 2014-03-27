<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\TermDuplicateDetector;

/**
 * Validator for checking that entity labels are unique (per language).
 * This is used to make sure that Properties have unique labels.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelDescriptionUniquenessValidator implements EntityValidator {

	/**
	 * @var TermDuplicateDetector
	 */
	protected $duplicateDetector;

	/**
	 * @param TermDuplicateDetector $duplicateDetector
	 */
	function __construct( TermDuplicateDetector $duplicateDetector ) {
		$this->duplicateDetector = $duplicateDetector;
	}

	/**
	 * @see OnSaveValidator::validate()
	 *
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity ) {
		$result = $this->duplicateDetector->detectLabelDescriptionConflictsForEntity( $entity );
		return $result;
	}

}