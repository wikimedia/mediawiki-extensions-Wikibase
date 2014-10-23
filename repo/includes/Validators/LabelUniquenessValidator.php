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
	private $duplicateDetector;

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
		$labels = $entity->getLabels();

		return $this->duplicateDetector->detectTermConflicts(
			$entity->getType(),
			$labels,
			null,
			$entity->getId()
		);
	}

	/**
	 * @see FingerprintValidator::validateFingerprint()
	 *
	 * @param Fingerprint $fingerprint
	 * @param EntityId $entityId
	 * @param string[]|null $languageCodes
	 *
	 * @return Result
	 */
	public function validateFingerprint(
		Fingerprint $fingerprint,
		EntityId $entityId,
		array $languageCodes = null
	) {
		$labels = array_map(
			function( Term $term ) {
				return $term->getText();
			},
			iterator_to_array( $fingerprint->getLabels()->getIterator() )
		);

		if ( $languageCodes !== null ) {
			$labels = array_intersect_key( $labels, array_flip( $languageCodes ) );
		}

		// nothing to do
		if ( empty( $labels ) ) {
			return Result::newSuccess();
		}

		return $this->duplicateDetector->detectTermConflicts(
			$entityId->getEntityType(),
			$labels,
			null,
			$entityId
		);
	}

}
