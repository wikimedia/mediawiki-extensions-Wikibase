<?php

namespace Wikibase;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;

/**
 * Detector of label/description uniqueness constraint violations.
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
	 * Report errors about other entities of the same type using the same label
	 * in the same language.
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 * @param EntityId $ignoreConflictsWith Another ID to ignore conflicts with.
	 *        Useful when merging entities.
	 *
	 * @return Result. If there are conflicts, $result->isValid() will return false and
	 *         $result->getErrors() will return a non-empty list of Error objects.
	 */
	public function detectLabelConflictsForEntity( Entity $entity, EntityId $ignoreConflictsWith = null ) {
		$labels = $entity->getLabels();

		$ignore = array( $entity->getId() );

		if ( $ignoreConflictsWith ) {
			$ignore[] = $ignoreConflictsWith;
		}

		return $this->detectTermConflicts( $labels, null, $ignore );
	}

	/**
	 * Report errors about other entities of the same type using the same combination
	 * of label and description, in the same language.
	 *
	 * @since 0.5
	 *
	 * @param Entity $entity
	 * @param EntityId $ignoreConflictsWith Another ID to ignore conflicts with.
	 *        Useful when merging entities.
	 *
	 * @return Result. If there are conflicts, $result->isValid() will return false and
	 *         $result->getErrors() will return a non-empty list of Error objects.
	 */
	public function detectLabelDescriptionConflictsForEntity( Entity $entity, EntityId $ignoreConflictsWith = null ) {
		$labels = $entity->getLabels();
		$descriptions = $entity->getDescriptions();

		$ignore = array( $entity->getId() );

		if ( $ignoreConflictsWith ) {
			$ignore[] = $ignoreConflictsWith;
		}

		return $this->detectTermConflicts( $labels, $descriptions, $ignore );
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
	 * @param array $labels An associative array of labels,
	 *        with language codes as the keys.
	 * @param array|null $descriptions An associative array of descriptions,
	 *        with language codes as the keys.
	 * @param EntityId[]|null $ignoreConflicts Ids of entities to ignore conflicts with.
	 *        This would typically contain the ID of the entity the terms are defined on,
	 *        and perhaps an additional entity during a merge.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return Result. If there are conflicts, $result->isValid() will return false and
	 *         $result->getErrors() will return a non-empty list of Error objects.
	 *         The error code will be either 'label-conflict' or 'label-with-description-conflict',
	 *         depending on whether descriptions where given.
	 *
	 * @todo Accept Term[] and/or TermList objects for $labels and $descriptions
	 */
	public function detectTermConflicts( $labels, $descriptions, array $ignoreConflicts = null ) {
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
			$termSpecs = $this->buildLabelConflictSpecs( $labels, $descriptions );
			$errorCode = 'label-conflict';
		} else {
			$termSpecs = $this->buildLabelDescriptionConflictSpecs( $labels, $descriptions );
			$errorCode = 'label-with-description-conflict';
		}

		$conflictingTerms = $this->findConflictingTerms( $termSpecs, $ignoreConflicts );

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
	 * @param array|null $labels An associative array of labels,
	 *        with language codes as the keys.
	 * @param array|null $descriptions An associative array of descriptions,
	 *        with language codes as the keys.
	 *
	 * @return array An array suitable for use with TermIndex::getMatchingTermCombination().
	 */
	private function buildLabelDescriptionConflictSpecs( array $labels, array $descriptions ) {
		$termSpecs = array();

		foreach ( $labels as $lang => $label ) {
			if ( !isset( $descriptions[$lang] ) ) {
				// If there's no description, there will be no conflict
				continue;
			}

			$label = new Term( array(
				'termLanguage' => $lang,
				'termText' => $label,
				'termType' => Term::TYPE_LABEL,
			) );

			$description = new Term( array(
				'termLanguage' => $lang,
				'termText' => $descriptions[$lang],
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
	 * @param array $labels An associative array mapping language codes to
	 *        records. Reach record is an associative array with they keys "label" and
	 *        "description", providing a label and description for each language.
	 *        Both the label and the description for a language may be null.
	 *
	 * @return array An array suitable for use with TermIndex::getMatchingTermCombination().
	 */
	private function buildLabelConflictSpecs( array $labels ) {
		$termSpecs = array();

		foreach ( $labels as $lang => $label ) {
			$label = new Term( array(
				'termLanguage' => $lang,
				'termText' => $label,
				'termType' => Term::TYPE_LABEL,
			) );

			$termSpecs[] = array( $label );
		}

		return $termSpecs;
	}

	/**
	 * @param array $termSpecs as returned by buildXxxTermSpecs
	 * @param EntityId[]|null $ignoreConflicts Ids of entities to ignore conflicts with.
	 *        This would typically contain the ID of the entity the terms are defined on,
	 *        and perhaps an additional entity during a merge.
	 *
	 * @return Term[]
	 */
	private function findConflictingTerms( array $termSpecs, array $ignoreConflicts = null ) {
		if ( empty( $termSpecs ) ) {
			return array();
		}

		// ugly hack
		$entityId = ( empty( $ignoreConflicts ) ? null : reset( $ignoreConflicts ) );

		// FIXME: Do not run this when running test using MySQL as self joins fail on temporary tables.
		if ( !defined( 'MW_PHPUNIT_TEST' )
			|| !( $this->termFinder instanceof TermSqlIndex )
			|| wfGetDB( DB_MASTER )->getType() !== 'mysql' ) {

			$foundTerms = $this->termFinder->getMatchingTermCombination(
				$termSpecs,
				Term::TYPE_LABEL,
				$entityId === null ? null : $entityId->getEntityType(),
				$entityId
			);
		} else {
			$foundTerms = array();
		}

		$foundTerms = $this->filterTerms( $foundTerms, $ignoreConflicts );
		return $foundTerms;
	}

	private function filterTerms( $terms, $ignoreEntities ) {
		$ignoreIds = array_map(
			function ( EntityId $id ) {
				return $id->getSerialization();
			},
			$ignoreEntities
		);

		$terms = array_filter(
			$terms,
			function ( Term $term ) use ( $ignoreIds ) {
				$id = $term->getEntityId()->getSerialization();
				return !in_array( $id, $ignoreIds );
			}
		);

		return $terms;
	}

	/**
	 * @param string $message Plain text message (english)
	 * @param string $errorCode Error code (for later localization)
	 * @param Term[] $terms The conflicting terms.
	 *
	 * @return array
	 */
	private function termsToErrors( $message, $errorCode, $terms ) {
		$errors = array();

		/* @var Term $term */
		foreach ( $terms as $term ) {
			$errors[] = Error::newError(
				$message,
				$term->getType(),
				$errorCode,
				array(
					$term->getText(),
					$term->getLanguage(),
					$term->getEntityId()
				) );
		}

		return $errors;
	}

}