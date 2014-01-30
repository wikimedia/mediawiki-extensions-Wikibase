<?php

namespace Wikibase;

use Status;
use Diff\Diff;

/**
 * Detector of label+description uniqueness constraint violations.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Adam Shorland
 */
class LabelDescriptionDuplicateDetector {

	/**
	 * @var TermCombinationMatchFinder
	 */
	private $termCache;

	/**
	 * @param TermCombinationMatchFinder $termCache
	 *
	 * @since 0.5
	 */
	public function __construct( TermCombinationMatchFinder $termCache ) {
		$this->termCache = $termCache;
	}

	/**
	 * Looks for label+description violations in the provided Entity using
	 * the provided TermIndex. If there is no such conflict, an empty array is returned.
	 * If there is, an array with first label and then description is returned,
	 * both objects being a Term.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @return Term[]
	 */
	public function getConflictingTerms( Entity $entity ) {
		$terms = array();

		foreach ( $entity->getLabels() as $langCode => $labelText ) {
			$description = $entity->getDescription( $langCode );

			if ( $description !== false ) {
				$label = new Term( array(
					'termLanguage' => $langCode,
					'termText' => $labelText,
					'termType' => Term::TYPE_LABEL,
				) );

				$description = new Term( array(
					'termLanguage' => $langCode,
					'termText' => $description,
					'termType' => Term::TYPE_DESCRIPTION,
				) );

				$terms[] = array( $label, $description );
			}
		}

		if ( empty( $terms ) ) {
			return array();
		}

		$foundTerms = $this->termCache->getMatchingTermCombination(
			$terms,
			null,
			$entity->getType(),
			$entity->getId() === null ? null : $entity->getId()
		);

		return $foundTerms;
	}

	/**
	 * Looks for label+description violations in the provided Entity using the provided TermIndex.
	 * If there is a conflict affected by the provided label and description diffs, a fatal error
	 * will be added to the provided status. If both diffs are not provided, any conflict will
	 * result in a fatal error being added.
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity The Entity for which to check if it has any non-unique label+description pairs
	 * @param Status $status The status to which to add an error if there is a violation
	 * @param Diff|null $labelsDiff
	 * @param Diff|null $descriptionDiff
	 */
	public function addLabelDescriptionConflicts(
		Entity $entity,
		Status $status,
		Diff $labelsDiff = null,
		Diff $descriptionDiff = null
	) {
		$foundTerms = $this->getConflictingTerms( $entity );

		if ( !empty( $foundTerms ) ) {
			/**
			 * @var Term $label
			 * @var Term $description
			 */
			list( $label, $description ) = $foundTerms;

			if ( ( $labelsDiff === null && $descriptionDiff === null )
				|| $this->languageAffectedByDiff( $label->getLanguage(), $labelsDiff, $descriptionDiff ) ) {

				$status->fatal(
					'wikibase-error-label-not-unique-item',
					$label->getText(),
					$label->getLanguage(),
					$label->getEntityId(),
					$description->getText()
				);
			}
		}
	}

	/**
	 * Returns if either of the provided label and description diffs affect a certain language.
	 *
	 * @since 0.4
	 *
	 * @param string $languageCode
	 * @param Diff|null $labelsDiff
	 * @param Diff|null $descriptionDiff
	 *
	 * @return boolean
	 */
	protected function languageAffectedByDiff( $languageCode, Diff $labelsDiff = null, Diff $descriptionDiff = null ) {
		$c = $labelsDiff->getOperations();

		if ( $labelsDiff !== null && array_key_exists( $languageCode, $c ) ) {
			return true;
		}

		if ( $descriptionDiff !== null && array_key_exists( $languageCode, $descriptionDiff->getOperations() ) ) {
			return true;
		}

		return false;
	}

}