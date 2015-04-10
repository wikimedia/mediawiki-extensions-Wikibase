<?php

namespace Wikibase;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Utility class for managing a search result.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermSearchResult {

	/**
	 * @var TermSearchHit[]
	 */
	private $hits = array();

	/**
	 * @param Term $term
	 */
	public function addTerm( Term $term ) {
		$key = $term->getEntityId()->getSerialization();

		if ( !isset( $this->hits[$key] ) ) {
			$this->hits[$key] = new TermSearchHit( $term->getEntityId() );
		}

		$this->hits[$key]->addTerm( $term );
	}

	/**
	 * @param Term[] $terms
	 */
	public function addAllTerms( array $terms ) {
		foreach ( $terms as $t ) {
			$this->addTerm( $t );
		}
	}

	/**
	 * Returns the search hits, sorted by rank.
	 *
	 * @return TermSearchHit[] Hits, keyed by entity ID string
	 */
	public function getHits( $limit = 0 ) {
		usort( $this->hits, function( TermSearchHit $a, TermSearchHit $b ) {
			return $b->getScore() - $a->getScore(); // descending
		} );

		if ( $limit > 0 ) {
			return array_slice( $this->hits, 0, $limit );
		} else {
			return $this->hits;
		}
	}

	/**
	 * Returns the EntityIds of the search hits, sorted by rank.
	 *
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	public function getEntityIds( $limit = 0 ) {
		return array_map( function( TermSearchHit $hit ) {
			return $hit->getEntityId();
		}, $this->getHits( $limit ) );
	}

	/**
	 * The number of hits
	 *
	 * @return int
	 */
	public function getSize() {
		return count( $this->hits );
	}

}
