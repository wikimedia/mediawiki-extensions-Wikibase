<?php

namespace Wikibase;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Validators\UniquenessViolation;

/**
 * Detector of label/description uniqueness constraint violations.
 *
 * @todo: Fold this into TermCombinationMatchFinder resp. TermIndex
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class LabelDescriptionDuplicateDetector {

	/**
	 * @var TermCombinationMatchFinder
	 */
	private $termFinder;

	/**
	 * @param TermCombinationMatchFinder $termFinder
	 */
	public function __construct( TermCombinationMatchFinder $termFinder ) {
		$this->termFinder = $termFinder;
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

		if ( $descriptions === null ) {
			$termSpecs = $this->buildLabelConflictSpecs( $labels );
			$errorCode = 'label-conflict';
		} else {
			$termSpecs = $this->buildLabelDescriptionConflictSpecs( $labels, $descriptions );
			$errorCode = 'label-with-description-conflict';
		}

		$conflictingTerms = $this->findConflictingTerms( $termSpecs, $entityId );

		if ( $conflictingTerms ) {
			$errors = $this->termsToErrors( 'found conflicting terms', $errorCode, $conflictingTerms );
			return Result::newError( $errors );
		} else {
			return Result::newSuccess();
		}
	}

	/**
	 * Builds a term spec array suitable for finding items with any of the given combinations
	 * of label and description for a given language. This applies only for languages for
	 * which both label and description are given in $terms.
	 *
	 * @param string[] $labels An associative array of labels,
	 *        with language codes as the keys.
	 * @param string[] $descriptions An associative array of descriptions,
	 *        with language codes as the keys.
	 *
	 * @return array[] An array suitable for use with TermIndex::getMatchingTermCombination().
	 */
	private function buildLabelDescriptionConflictSpecs( array $labels, array $descriptions ) {
		$termSpecs = array();

		foreach ( $labels as $languageCode => $label ) {
			if ( !isset( $descriptions[$languageCode] ) ) {
				// If there's no description, there will be no conflict
				continue;
			}

			$label = new Term( array(
				'termLanguage' => $languageCode,
				'termText' => $label,
				'termType' => Term::TYPE_LABEL,
			) );

			$description = new Term( array(
				'termLanguage' => $languageCode,
				'termText' => $descriptions[$languageCode],
				'termType' => Term::TYPE_DESCRIPTION,
			) );

			$termSpecs[] = array( $label, $description );
		}

		return $termSpecs;
	}

	/**
	 * Builds a term spec array suitable for finding entities with any of the given labels
	 * for a given language.
	 *
	 * @param string[] $labels An associative array of labels,
	 *        with language codes as the keys.
	 *
	 * @return array[] An array suitable for use with TermIndex::getMatchingTermCombination().
	 */
	private function buildLabelConflictSpecs( array $labels ) {
		$termSpecs = array();

		foreach ( $labels as $languageCode => $label ) {
			$label = new Term( array(
				'termLanguage' => $languageCode,
				'termText' => $label,
				'termType' => Term::TYPE_LABEL,
			) );

			$termSpecs[] = array( $label );
		}

		return $termSpecs;
	}

	/**
	 * @param array[] $termSpecs as returned by $this->build...ConflictSpecs()
	 * @param EntityId $entityId
	 *
	 * @return Term[]
	 */
	private function findConflictingTerms( array $termSpecs, EntityId $entityId = null ) {
		if ( empty( $termSpecs ) ) {
			return array();
		}

		// FIXME: Do not run this when running test using MySQL as self joins fail on temporary tables.
		if ( !defined( 'MW_PHPUNIT_TEST' )
			|| !( $this->termFinder instanceof TermSqlIndex )
			|| wfGetDB( DB_MASTER )->getType() !== 'mysql'
		) {
			$foundTerms = $this->termFinder->getMatchingTermCombination(
				$termSpecs,
				Term::TYPE_LABEL,
				$entityId === null ? null : $entityId->getEntityType(),
				$entityId
			);
		} else {
			$foundTerms = array();
		}

		return $foundTerms;
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
