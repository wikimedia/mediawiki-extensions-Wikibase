<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LabelDescriptionEqualDetector;

/**
 * @license GPL-2.0-or-later
 * @author Greta Doci
 */
class LabelDescriptionNotEqualValidator implements EntityValidator, FingerprintValidator {

	/**
	 * @var LabelDescriptionEqualDetector
	 */
	private $equalDetector;

	public function __construct( LabelDescriptionEqualDetector $equalDetector ) {
		$this->equalDetector = $equalDetector;
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
			return $this->equalDetector->detectLabelDescriptionEqual(
				$entity->getLabels()->toTextArray(),
				$entity->getDescriptions()->toTextArray()
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

		// Nothing to do if there are no labels OR no descriptions.
		if ( empty( $labels ) || empty( $descriptions ) ) {
			return Result::newSuccess();
		}

		return $this->equalDetector->detectLabelDescriptionEqual(
			$labels,
			$descriptions
		);
	}

}
