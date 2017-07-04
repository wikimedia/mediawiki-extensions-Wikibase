<?php

namespace Wikibase;

use InvalidArgumentException;
use ValueValidators\Result;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Repo\Validators\UniquenessViolation;

/**
 * Detector of label/description uniqueness constraint violations.
 * Builds on top of LabelConflictFinder adding handling of self-conflicts and localization.
 *
 * @see LabelConflictFinder
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class LabelDescriptionDuplicateDetector {

	/**
	 * @var LabelConflictFinder
	 */
	private $conflictFinder;

	public function __construct( LabelConflictFinder $conflictFinder ) {
		$this->conflictFinder = $conflictFinder;
	}

	/**
	 * Detects conflicting labels and aliases. A conflict arises when another entity has the same
	 * label or alias for a given language as is present in $label or $aliases. If $aliases is null,
	 * only conflicts between labels are considered. If $aliases is not null (but possibly empty),
	 *  conflicts are also detected between labels and aliases, in any combination.
	 *
	 * @param string $entityType The type of entity to search for conflicts.
	 * @param string[] $labels An associative array of labels,
	 *        with language codes as the keys.
	 * @param array[]|null $aliases Aliases to be considered to be conflicting with labels.
	 *        Ignored if descriptions are given.
	 * @param EntityId|null $ignoreEntityId Conflicts with this entity will be
	 *        considered self-conflicts and ignored.
	 *
	 * @throws InvalidArgumentException
	 * @return Result
	 */
	public function detectLabelConflicts(
		$entityType,
		array $labels,
		array $aliases = null,
		EntityId $ignoreEntityId = null
	) {
		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string' );
		}

		// Conflicts can only arise if labels OR aliases are given.
		if ( empty( $labels ) && empty( $aliases ) ) {
			return Result::newSuccess();
		}

		$conflictingTerms = $this->conflictFinder->getLabelConflicts(
			$entityType,
			$labels,
			$aliases
		);

		if ( $ignoreEntityId ) {
			$conflictingTerms = $this->filterSelfConflicts( $conflictingTerms, $ignoreEntityId );
		}

		if ( !empty( $conflictingTerms ) ) {
			$errors = $this->termsToErrors( 'found conflicting terms', 'label-conflict', $conflictingTerms );
			return Result::newError( $errors );
		} else {
			return Result::newSuccess();
		}
	}

	/**
	 * Detects conflicting combinations of labels and descriptions. A conflict arises when an entity
	 * (other than the one given by $ignoreEntityId, if any) has the same combination of label and
	 * non-empty description for a given language as is present tin the $label and $description
	 * parameters.
	 *
	 * @param string $entityType The type of entity to search for conflicts.
	 * @param string[] $labels An associative array of labels,
	 *        with language codes as the keys.
	 * @param string[] $descriptions An associative array of descriptions,
	 *        with language codes as the keys.
	 * @param EntityId|null $ignoreEntityId Conflicts with this entity will be
	 *        considered self-conflicts and ignored.
	 *
	 * @throws InvalidArgumentException
	 * @return Result
	 */
	public function detectLabelDescriptionConflicts(
		$entityType,
		array $labels,
		array $descriptions,
		EntityId $ignoreEntityId = null
	) {
		if ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string' );
		}

		// Conflicts can only arise if both a label AND a description is given.
		if ( empty( $labels ) || empty( $descriptions ) ) {
			return Result::newSuccess();
		}

		$conflictingTerms = $this->conflictFinder->getLabelWithDescriptionConflicts(
			$entityType,
			$labels,
			$descriptions
		);

		if ( $ignoreEntityId ) {
			$conflictingTerms = $this->filterSelfConflicts( $conflictingTerms, $ignoreEntityId );
		}

		if ( !empty( $conflictingTerms ) ) {
			$errors = $this->termsToErrors( 'found conflicting terms', 'label-with-description-conflict', $conflictingTerms );
			return Result::newError( $errors );
		} else {
			return Result::newSuccess();
		}
	}

	/**
	 * @param string $message Plain text message (English)
	 * @param string $errorCode Error code (for later localization)
	 * @param TermIndexEntry[] $terms The conflicting terms.
	 *
	 * @return UniquenessViolation[]
	 */
	private function termsToErrors( $message, $errorCode, array $terms ) {
		$errors = [];

		foreach ( $terms as $term ) {
			$errors[] = new UniquenessViolation(
				$term->getEntityId(),
				$message,
				$errorCode,
				[
					$term->getText(),
					$term->getLanguage(),
					$term->getEntityId(),
				]
			);
		}

		return $errors;
	}

	/**
	 * @param TermIndexEntry[] $terms
	 * @param EntityId $entityId
	 *
	 * @return TermIndexEntry[]
	 */
	private function filterSelfConflicts( array $terms, EntityId $entityId ) {
		return array_filter(
			$terms,
			function ( TermIndexEntry $term ) use ( $entityId ) {
				return !$entityId->equals( $term->getEntityId() );
			}
		);
	}

}
