<?php

namespace Wikibase\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\FingerprintProvider;
use Wikibase\DataModel\Term\Term;
use Wikibase\LabelDescriptionDuplicateDetector;

/**
 * Validator for checking that the combination of an entity's label and description
 * are unique (per language). This is used to make sure that no two items have the same
 * label and description.
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
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validateEntity( EntityDocument $entity ) {
		if ( $entity instanceof FingerprintProvider ) {
			return $this->duplicateDetector->detectLabelDescriptionConflicts(
				$entity->getType(),
				$entity->getFingerprint()->getLabels()->toTextArray(),
				$entity->getFingerprint()->getDescriptions()->toTextArray(),
				$entity->getId()
			);
		}

		return Result::newSuccess();
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
		$labels = $fingerprint->getLabels()->toTextArray();
		$descriptions = $fingerprint->getDescriptions()->toTextArray();

		if ( $languageCodes !== null ) {
			$languageKeys = array_flip( $languageCodes );
			$labels = array_intersect_key( $labels, $languageKeys );
			$descriptions = array_intersect_key( $descriptions, $languageKeys );
		}

		// Nothing to do if there are no labels OR no descriptions, since
		// a conflict requires a label AND a description.
		if ( empty( $labels ) || empty( $descriptions ) ) {
			return Result::newSuccess();
		}

		return $this->duplicateDetector->detectLabelDescriptionConflicts(
			$entityId->getEntityType(),
			$labels,
			$descriptions,
			$entityId
		);
	}

}
