<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\LabelDescriptionDuplicateDetector;

/**
 * Validator for checking that entity labels and aliases are unique (per language).
 * This is used to make sure that Properties have unique labels and aliases.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class LabelUniquenessValidator implements EntityValidator {

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
		if ( $entity instanceof LabelsProvider ) {
			return $this->duplicateDetector->detectLabelConflicts(
				$entity->getType(),
				$entity->getLabels()->toTextArray(),
				// insert again when T104393 is resolved
				null, //$entity->getFingerprint()->getAliasGroups()->toTextArray(),
				$entity->getId()
			);
		}

		return Result::newSuccess();
	}

}
