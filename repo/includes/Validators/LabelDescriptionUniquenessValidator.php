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
class LabelDescriptionUniquenessValidator implements EntityValidator, FingerprintValidator {

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	private $duplicateDetector;

	/**
	 * @param LabelDescriptionDuplicateDetector $duplicateDetector
	 */
	public function __construct( LabelDescriptionDuplicateDetector $duplicateDetector ) {
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
		if ( !$entity->getId() ) {
			throw new \InvalidArgumentException( '$entity must have an ID set' );
		}

		$labels = $entity->getLabels();
		$descriptions = $entity->getDescriptions();

		return $this->duplicateDetector->detectTermConflicts(
			$entity->getType(),
			$labels,
			$descriptions,
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

		$descriptions = array_map(
			function( Term $term ) {
				return $term->getText();
			},
			iterator_to_array( $fingerprint->getDescriptions()->getIterator() )
		);

		if ( $languageCodes !== null ) {
			$languageKeys = array_flip( $languageCodes );
			$labels = array_intersect_key( $labels, $languageKeys );
			$descriptions = array_intersect_key( $descriptions, $languageKeys );
		}

		// nothing to do
		if ( empty( $labels ) && empty( $descriptions ) ) {
			return Result::newSuccess();
		}

		return $this->duplicateDetector->detectTermConflicts(
			$entityId->getEntityType(),
			$labels,
			$descriptions,
			$entityId
		);
	}

}
