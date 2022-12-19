<?php

namespace Wikibase\Repo\Validators;

use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 * @author Greta Doci
 */
class LabelDescriptionNotEqualValidator implements EntityValidator {

	/**
	 * @see EntityValidator::validate()
	 *
	 * @param EntityDocument $entity
	 *
	 * @return Result
	 */
	public function validateEntity( EntityDocument $entity ) {
		if ( $entity instanceof LabelsProvider && $entity instanceof DescriptionsProvider ) {
			return $this->detectLabelDescriptionEqual(
				$entity->getLabels()->toTextArray(),
				$entity->getDescriptions()->toTextArray()
			);
		}

		return Result::newSuccess();
	}

	/**
	 * @param TermList $labels
	 * @param TermList $descriptions
	 * @param string[]|null $languageCodes
	 *
	 * @return Result
	 */
	public function validateLabelAndDescription(
		TermList $labels,
		TermList $descriptions,
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

		return $this->detectLabelDescriptionEqual(
			$labels,
			$descriptions
		);
	}

	/**
	 * Detects when labels and descriptions are equal. A conflict arises when an entity
	 * has the same combination of label and non-empty description for a given
	 * language as is present in the $label and $description parameters.
	 *
	 * @param string[] $labels An associative array of labels,
	 *        with language codes as the keys.
	 * @param string[] $descriptions An associative array of descriptions,
	 *        with language codes as the keys.
	 *
	 * @return Result
	 */
	private function detectLabelDescriptionEqual(
		array $labels,
		array $descriptions
	) {
		foreach ( $labels as $languageCode => $label ) {
			if ( array_key_exists( $languageCode, $descriptions ) ) {
				if ( $descriptions[$languageCode] === $label ) {
					return Result::newError( [
						new NotEqualViolation( 'label should not be equal to description',
							'label-equals-description', [ $languageCode ] ),
					] );
				}
			}
		}

		return Result::newSuccess();
	}

}
