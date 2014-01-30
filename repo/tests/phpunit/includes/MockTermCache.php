<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Item;
use Wikibase\Term;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MockTermCache implements \Wikibase\TermCombinationMatchFinder {

	/**
	 * @var Term[]
	 */
	protected $terms;

	public function __construct() {
		$terms = array();

		$terms[] = new Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'label-en',
		) );

		$terms[] = new Term( array(
			'termType' => Term::TYPE_LABEL,
			'termLanguage' => 'de',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'label-de',
		) );

		$terms[] = new Term( array(
			'termType' => Term::TYPE_DESCRIPTION,
			'termLanguage' => 'en',
			'entityId' => 42,
			'entityType' => Item::ENTITY_TYPE,
			'termText' => 'description-en',
		) );

		$this->terms = $terms;
	}

	/**
	 * @see \Wikibase\TermCombinationMatchFinder::getMatchingTermCombination
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param EntityId|null $excludeId
	 *
	 * @return array
	 */
	public function getMatchingTermCombination( array $terms, $termType = null, $entityType = null, EntityId $excludeId = null ) {
		/**
		 * @var Term[] $termPair
		 * @var Term[] $matchingTerms
		 */
		foreach ( $terms as $termPair ) {
			$matchingTerms = array();

			$id = null;
			$type = null;

			foreach ( $termPair as $term ) {
				foreach ( $this->terms as $storedTerm ) {
					if ( $term->getText() === $storedTerm->getText()
						&& $term->getLanguage() === $storedTerm->getLanguage()
						&& $term->getType() === $storedTerm->getType() ) {

						if ( $id === null ) {
							$id = $term->getEntityId();
							$type = $term->getEntityType();
							$matchingTerms[] = $term;
						}
						elseif ( $id === $term->getEntityId() && $type === $term->getEntityType() ) {
							$matchingTerms[] = $term;
						}
					}
				}
			}

			if ( count( $matchingTerms ) === count( $termPair ) ) {
				return $matchingTerms;
			}
		}

		return array();
	}

}