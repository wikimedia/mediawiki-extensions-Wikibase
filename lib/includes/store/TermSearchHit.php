<?php

namespace Wikibase;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Value object representing a hit of a term search.
 * A hit has an entity ID, a weight, and any number of Term objects.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermSearchHit {

	/**
	 * @var EntityId
	 */
	private $entityId;

	/**
	 * @var float
	 */
	private $score = 0.0;

	/**
	 * @var Term[]
	 */
	private $terms = array();

	/**
	 * @param EntityId $entityId
	 */
	public function __construct( EntityId $entityId ) {
		$this->entityId = $entityId;
	}

	/**
	 * @return EntityId
	 */
	public function getEntityId() {
		return $this->entityId;
	}

	/**
	 * @return Term[]
	 */
	public function getTerms() {
		usort( $this->terms, function( Term $a, Term $b ) {
			return $b->getWeight() - $a->getWeight(); // descending
		} );

		return $this->terms;
	}

	/**
	 * @return float
	 */
	public function getScore() {
		return $this->score;
	}

	/**
	 * @param Term $term
	 */
	public function addTerm( Term $term ) {
		if ( $term->getEntityId() && !$this->entityId->equals( $term->getEntityId() ) ) {
			throw new InvalidArgumentException( 'Term entry refers to unrelated entity' );
		}

		$this->score = max( $this->score, $term->getWeight() );
		$this->terms[] = $term;
	}

}
