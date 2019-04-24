<?php

namespace Wikibase;

use ValueValidators\Result;
use Wikibase\Repo\Validators\NotEqualViolation;

/**
 * Detector of label and description equality.
 *
 * @license GPL-2.0-or-later
 * @author Greta Doci
 */
class LabelDescriptionEqualDetector {

	/**
	 * Detects when labels and descriptions are equal. A conflict arises when an entity
	 * (other than the one given by $ignoreEntityId, if any) has the same combination of label and
	 * non-empty description for a given language as is present tin the $label and $description
	 * parameters.
	 *
	 * @param string[] $labels An associative array of labels,
	 *        with language codes as the keys.
	 * @param string[] $descriptions An associative array of descriptions,
	 *        with language codes as the keys.
	 *
	 * @return Result
	 */
	public function detectLabelDescriptionEqual(
		array $labels,
		array $descriptions
	) {
		foreach ( $labels as $languageCode => $label ) {
			if ( array_key_exists( $languageCode, $descriptions ) ) {
				if ( $descriptions[$languageCode] === $label ) {
					return Result::newError( [
						new NotEqualViolation( 'label should not be equal to description',
							'label-equals-description', [ $languageCode ] )
					] );
				}
			}
		}

		return Result::newSuccess();
	}

}
