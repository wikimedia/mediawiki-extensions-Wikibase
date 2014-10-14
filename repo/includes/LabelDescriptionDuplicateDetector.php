<?php

namespace Wikibase;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Validators\UniquenessViolation;

/**
 * Detector of label/description uniqueness constraint violations.
 *
 * @todo: Fold this into LabelConflictFinder resp. TermIndex
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelDescriptionDuplicateDetector {

	/**
	 * @var LabelConflictFinder
	 */
	private $conflictFinder;

	/**
	 * @param LabelConflictFinder $conflictFinder
	 */
	public function __construct( LabelConflictFinder $conflictFinder ) {
		$this->conflictFinder = $conflictFinder;
	}

	/**
	 * Validates the uniqueness constraints on the combination of label and description given
	 * for all the languages in $terms. This will apply a different logic for
	 * Items than for Properties: while the label of a Property must be unique (per language),
	 * only an Item's combination of label and description must be unique, and even that only
	 * if the description is actually set.
	 *
	 * @since 0.5
	 *
	 * @param string[] $labels An associative array of labels,
	 *        with language codes as the keys.
	 * @param string[]|null $descriptions An associative array of descriptions,
	 *        with language codes as the keys.
	 * @param EntityId $entityId The Id of the Entity the terms come from. Conflicts
	 *        with this entity will be considered self-conflicts and ignored.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return Result. If there are conflicts, $result->isValid() will return false and
	 *         $result->getErrors() will return a non-empty list of Error objects.
	 *         The error code will be either 'label-conflict' or 'label-with-description-conflict',
	 *         depending on whether descriptions where given.
	 */
	public function detectTermConflicts( array $labels, $descriptions, EntityId $entityId = null ) {
		if ( !is_array( $labels ) ) {
			throw new InvalidArgumentException( '$labels must be an array' );
		}

		if ( $descriptions !== null && !is_array( $descriptions ) ) {
			throw new InvalidArgumentException( '$descriptions must be an array or null' );
		}

		if ( empty( $labels ) && empty( $descriptions ) ) {
			return Result::newSuccess();
		}

		$conflictingTerms = $this->conflictFinder->getLabelConflicts(
			$entityId === null ? null : $entityId->getEntityType(),
			$labels,
			$descriptions,
			$entityId
		);

		if ( $conflictingTerms ) {
			$errorCode = $descriptions === null ? 'label-conflict' : 'label-with-description-conflict';
			$errors = $this->termsToErrors( 'found conflicting terms', $errorCode, $conflictingTerms );
			return Result::newError( $errors );
		} else {
			return Result::newSuccess();
		}
	}

	/**
	 * @param string $message Plain text message (English)
	 * @param string $errorCode Error code (for later localization)
	 * @param Term[] $terms The conflicting terms.
	 *
	 * @return array
	 */
	private function termsToErrors( $message, $errorCode, $terms ) {
		$errors = array();

		/* @var Term $term */
		foreach ( $terms as $term ) {
			$errors[] = new UniquenessViolation(
				$term->getEntityId(),
				$message,
				$errorCode,
				array(
					$term->getText(),
					$term->getLanguage(),
					$term->getEntityId(),
				)
			);
		}

		return $errors;
	}

}
