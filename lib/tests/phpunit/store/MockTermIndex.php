<?php

namespace Wikibase\Test;

use Exception;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Term;
use Wikibase\TermIndex;

/**
 * Mock implementation of TermIndex.
 *
 * @note: this uses internal knowledge about which functions of TermIndex are used
 * by PropertyLabelResolver, and how.
 *
 * @todo: make a fully functional mock conforming to the contract of the TermIndex
 * interface and passing tests for that interface. Only then will TermPropertyLabelResolverTest
 * be a true blackbox test.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockTermIndex implements TermIndex {

	/**
	 * @var Term[]
	 */
	protected $terms;

	/**
	 * @param Term[] $terms
	 */
	public function __construct( $terms ) {
		$this->terms = $terms;
	}

	/**
	 * @see LabelConflictFinder::getLabelConflicts
	 *
	 * @param string $entityType The relevant entity type
	 * @param string $labels The label to look for
	 * @param string|null $descriptions The description to consider, if descriptions are relevant.
	 * @param EntityId|null $excludeId Ignore conflicts with this entity ID (for ignoring self-conflicts)
	 *
	 * @return EntityId[]
	 */
	public function getLabelConflicts( $entityType, $labels, $descriptions = null, \Wikibase\DataModel\Entity\EntityId $excludeId = null ) {
		/**
		 * @var Term[] $termPair
		 * @var Term[] $matchingTerms
		 */
		foreach ( $terms as $termCombo ) {
			$matchesPerEntity = null;

			/** @var Term $term */
			foreach ( $termCombo as $term ) {
				$matchesPerEntityForTerm = $this->findMatchesPerEntity(
					$term->getText(),
					$term->getLanguage(),
					$term->getType(),
					$entityType,
					$excludeId
				);

				if ( $matchesPerEntity === null ) {
					$matchesPerEntity = $matchesPerEntityForTerm;
				} else {
					$matchesPerEntity = array_intersect_key( $matchesPerEntity, $matchesPerEntityForTerm );
				}
			}

			if ( !empty( $matchesPerEntity ) ) {
				return reset( $matchesPerEntity );
			}
		}

		return array();
	}

	private function findMatchesPerEntity( $text, $language, $termType = null, $entityType = null, EntityId $excludeId = null ) {
		$matchingTerms = array();

		foreach ( $this->terms as $storedTerm ) {

			if ( $text !== $storedTerm->getText() ) {
				continue;
			}

			if ( $language !== $storedTerm->getLanguage() ) {
				continue;
			}

			if ( $entityType && $entityType !== $storedTerm->getEntityType() ) {
				continue;
			}

			if ( $termType && $termType !== $storedTerm->getType() ) {
				continue;
			}

			if ( $excludeId !== null && $storedTerm->getEntityId()->equals( $excludeId ) ) {
				continue;
			}

			$matchingTerms[] = $storedTerm;
		}

		return $matchingTerms;
	}

	/**
	 * @throws Exception always
	 */
	public function getEntityIdsForLabel( $label, $languageCode = null, $entityType = null, $fuzzySearch = false ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function saveTermsOfEntity( Entity $entity ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function deleteTermsOfEntity( EntityId $entityId ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function getTermsOfEntity( EntityId $id ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function getTermsOfEntities( array $ids, $entityType, $language = null ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function termExists( $termValue, $termType = null, $termLanguage = null, $entityType = null ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * Implemented to fit the need of PropertyLabelResolver.
	 *
	 * @note: The $options parameters is ignored. The language to get is determined by the
	 * language of the first Term in $terms. $The termType and $entityType parameters are used,
	 * but the termType and entityType fields of the Terms in $terms are ignored.
	 *
	 * @param Term[] $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param array $options
	 *
	 * @return Term[]
	 */
	public function getMatchingTerms( array $terms, $termType = null, $entityType = null, array $options = array() ) {
		$matchingTerms = array();

		$language = $terms[0]->getLanguage();

		foreach ( $this->terms as $term ) {
			if ( $term->getLanguage() === $language
				&& $term->getEntityType() === $entityType
				&& $term->getType() === $termType
			) {

				$matchingTerms[] = $term;
			}
		}

		return $matchingTerms;
	}

	/**
	 * @throws Exception always
	 */
	public function getMatchingIDs( array $terms, $entityType, array $options = array() ) {
		throw new Exception( 'not implemented by mock class ' );
	}

	/**
	 * @throws Exception always
	 */
	public function clear() {
		$this->terms = array();
	}

}
