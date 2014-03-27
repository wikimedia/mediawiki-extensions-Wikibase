<?php

namespace Wikibase;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;

/**
 * Detector of label+description uniqueness constraint violations.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermDuplicateDetector {

	/**
	 * @var TermIndex
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
	 * @param EntityId $entityId
	 * @param array $terms An associative array mapping language codes to
	 *        records. Reach record is an associative array with they keys "label" and
	 *        "description", providing a label and description for each language.
	 *        Both the label and the description for a language may be null.
	 *
	 * @throws InvalidArgumentException if $terms is empty
	 * @return Result
	 */
	public function detectTermDuplicates( EntityId $entityId, array $terms ) {
		if ( empty( $terms ) ) {
			throw new InvalidArgumentException( '$terms must not be empty' );
		}

		//TODO: how to support more entity types here?!
		if ( $entityId->getEntityType() === Property::ENTITY_TYPE ) {
			$errorCode = 'label-not-unique-wikibase-property';
			$termSpecs = $this->buildUniqueLabelSpecs( $terms );
		} /*else if ( $entityId->getEntityType() === Query::ENTITY_TYPE ) {
			$errorCode = 'wikibase-error-label-not-unique-wikibase-query';
			$termSpecs = $this->buildUniqueLabelSpecs( $terms );
		}*/ else {
			$errorCode = 'label-not-unique-item';
			$termSpecs = $this->buildUniqueLabelDescriptionPairSpecs( $terms );
		}

		$result = $this->detectTermConflicts( $entityId, $termSpecs, $errorCode );
		return $result;
	}

	/**
	 * Builds a term spec array suitable for finding items with any of the given combinations
	 * of label and description for a given language. This applies only for languages for
	 * which both label and description are given in $terms.
	 *
	 * @param array $terms An associative array mapping language codes to
	 *        records. Reach record is an associative array with they keys "label" and
	 *        "description", providing a label and description for each language.
	 *        Both the label and the description for a language may be null.
	 *
	 * @return array An array suitable for use with TermIndex::getMatchingTermCombination().
	 */
	private function buildUniqueLabelDescriptionPairSpecs( array $terms ) {
		$termSpecs = array();

		foreach ( $terms as $langCode => $entry ) {
			if ( !isset( $entry['label'] ) || !$entry['label'] ) {
				continue;
			}

			if ( !isset( $entry['description'] ) || !$entry['description'] ) {
				continue;
			}

			$label = new Term( array(
				'termLanguage' => $langCode,
				'termText' => $entry['label'],
				'termType' => Term::TYPE_LABEL,
			) );

			$description = new Term( array(
				'termLanguage' => $langCode,
				'termText' => $entry['description'],
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
	 * @param array $terms An associative array mapping language codes to
	 *        records. Reach record is an associative array with they keys "label" and
	 *        "description", providing a label and description for each language.
	 *        Both the label and the description for a language may be null.
	 *
	 * @return array An array suitable for use with TermIndex::getMatchingTermCombination().
	 */
	private function buildUniqueLabelSpecs( array $terms ) {
		$termSpecs = array();

		foreach ( $terms as $langCode => $entry ) {
			if ( !isset( $entry['label'] ) || !$entry['label'] ) {
				continue;
			}

			$label = new Term( array(
				'termLanguage' => $langCode,
				'termText' => $entry['label'],
				'termType' => Term::TYPE_LABEL,
			) );

			$termSpecs[] = array( $label );
		}

		return $termSpecs;
	}

	/**
	 * @param EntityId $entityId
	 * @param array $termSpecs as returned by BuildXxxTermSpecs
	 * @param string $errorCode
	 *
	 * @return Result
	 */
	private function detectTermConflicts( EntityId $entityId, array $termSpecs, $errorCode ) {
		if ( empty( $termSpecs ) ) {
			return Result::newSuccess();
		}

		// FIXME: Do not run this when running test using MySQL as self joins fail on temporary tables.
		if ( !defined( 'MW_PHPUNIT_TEST' )
			|| !( $this->termFinder instanceof TermSqlIndex )
			|| wfGetDB( DB_MASTER )->getType() !== 'mysql' ) {

			$foundTerms = $this->termFinder->getMatchingTermCombination(
				$termSpecs,
				Term::TYPE_LABEL,
				$entityId->getEntityType(),
				$entityId
			);
		} else {
			$foundTerms = array();
		}

		if ( $foundTerms ) {
			$errors = $this->termsToErrors( 'found conflicting terms', $errorCode, $foundTerms );
			return Result::newError( $errors );
		} else {
			return Result::newSuccess();
		}
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
			$errors[] = Error::newError( $message, null, $errorCode,
				array(
					$term->getType(),
					$term->getLanguage(),
					$term->getText(),
					$term->getEntityId()->getSerialization()
				) );
		}

		return $errors;
	}

}