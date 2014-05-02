<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\LabelDescriptionDuplicateDetector;

/**
 * Validator for checking that entity labels are unique (per language).
 * This is used to make sure that Properties have unique labels.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelUniquenessValidator implements EntityValidator {

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	protected $duplicateDetector;

	/**
	 * @param LabelDescriptionDuplicateDetector $duplicateDetector
	 */
	function __construct( LabelDescriptionDuplicateDetector $duplicateDetector ) {
		$this->duplicateDetector = $duplicateDetector;
	}

	/**
	 * @see EntityValidator::validate()
	 *
	 * @param Entity $entity
	 *
	 * @return Result
	 */
	public function validateEntity( Entity $entity ) {
		$result = $this->duplicateDetector->detectLabelConflictsForEntity( $entity );
		return $result;
	}

}