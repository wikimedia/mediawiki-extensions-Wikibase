<?php

namespace Wikibase\Lib\Tests\Store\Sql\MediaWikiTermStore;

use Wikibase\Lib\Store\Sql\MediaWikiTermStore\NormalizedTermStoreSchemaAccess;

class InMemoryNormalizedTermStoreAccess implements NormalizedTermStoreSchemaAccess {

	private $terms;
	private $lastId = 0;

	/**
	 * @inheritDoc
	 */
	public function acquireTermIds( array $termsArray ) {
		$ids = [];

		foreach ( $termsArray as $type => $termsOfType ) {
			foreach ( $termsOfType as $lang => $terms ) {
				$terms = (array)$terms;

				foreach ( $terms as $term ) {
					if ( isset( $this->terms[ $type ][ $lang ][ $term ] ) ) {
						$ids[] = $this->terms[ $type ][ $lang ][ $term ];
					} else {
						$this->terms[ $type ][ $lang ][ $term ] = ++$this->lastId;
						$ids[] = $this->lastId;
					}
				}
			}
		}

		return $ids;
	}

}
