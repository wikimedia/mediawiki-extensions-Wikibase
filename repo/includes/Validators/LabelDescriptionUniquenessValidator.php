<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LabelDescriptionDuplicateDetector;

/**
 * Validator for checking that the combination of an entity's label and description
 * are unique (per language). This is used to make sure that no two items have the same
 * label and description.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class LabelDescriptionUniquenessValidator implements EntityValidator, FingerprintValidator {

	/**
	 * @var LabelDescriptionDuplicateDetector
	 */
	private $duplicateDetector;

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
		if ( $entity instanceof LabelsProvider && $entity instanceof DescriptionsProvider ) {
			return $this->duplicateDetector->detectLabelDescriptionConflicts(
				$entity->getType(),
				$entity->getLabels()->toTextArray(),
				$entity->getDescriptions()->toTextArray(),
				$entity->getId()
			);
		}

		return Result::newSuccess();
	}

	/**
	 * @see FingerprintValidator::validateFingerprint()
	 *
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param EntityId $entityId
	 * @param string[]|null $languageCodes
	 *
	 * @return Result
	 */
	public function validateFingerprint(
		TermList $labels,
		TermList $descriptions,
		EntityId $entityId,
		array $languageCodes = null
	) {
		$labels = $labels->toTextArray();
		$descriptions = $descriptions->toTextArray();

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
