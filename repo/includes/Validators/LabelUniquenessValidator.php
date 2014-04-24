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
class LabelUniquenessValidator implements EntityValidator, FingerprintValidator {

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

	/**
	 * @see FingerprintValidator::validateFingerprint()
	 *
	 * @since 0.5
	 *
	 * @param Fingerprint $fingerprint
	 * @param EntityId|null $entityId Context for uniqueness checks: conflicts with this entity
	 *        are ignored.
	 * @param array|null $languages If given, the validation may be limited to the given languages;
	 *        This is intended for optimization for the common case of only a single language changing.
	 *
	 * @return Result
	 */
	public function validateFingerprint( Fingerprint $fingerprint, EntityId $entityId = null, $languages = null ) {
		$labels = array_map(
			function( Term $term ) { return $term->getText(); },
			iterator_to_array( $fingerprint->getLabels()->getIterator() )
		);

		return $this->duplicateDetector->detectTermConflicts( $labels, null, array( $entityId ) );
	}

}
