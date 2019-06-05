<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryTermIdsStore implements TermIdsAcquirer, TermIdsResolver, TermIdsCleaner {

	private $terms = [];
	private $lastId = 0;

	public function acquireTermIds( array $termsArray ): array {
		$ids = [];

		foreach ( $termsArray as $type => $termsOfType ) {
			foreach ( $termsOfType as $lang => $terms ) {
				$terms = (array)$terms;

				foreach ( $terms as $term ) {
					if ( !isset( $this->terms[$type][$lang][$term] ) ) {
						$this->terms[$type][$lang][$term] = ++$this->lastId;
					}

					$ids[] = $this->terms[$type][$lang][$term];
				}
			}
		}

		return $ids;
	}

	public function resolveTermIds( array $termIds ): array {
		$terms = [];
		foreach ( $this->terms as $type => $termsOfType ) {
			foreach ( $termsOfType as $lang => $termsOfLang ) {
				foreach ( $termsOfLang as $term => $id ) {
					if ( in_array( $id, $termIds ) ) {
						$terms[$type][$lang][] = $term;
					}
				}
			}
		}
		return $terms;
	}

	public function resolveGroupedTermIds( array $groupedTermIds ): array {
		return array_map( [ $this, 'resolveTermIds' ], $groupedTermIds );
	}

	public function cleanTermIds( array $termIds ) {
		$termIdsAsKeys = array_flip( $termIds );
		foreach ( $this->terms as $type => &$termsOfType ) {
			foreach ( $termsOfType as $lang => &$termsOfLang ) {
				foreach ( $termsOfLang as $term => $id ) {
					if ( array_key_exists( $id, $termIdsAsKeys ) ) {
						unset( $termsOfLang[$term] );
					}
				}
				if ( $termsOfLang === [] ) {
					unset( $termsOfType[$lang] );
				}
			}
			if ( $termsOfType === [] ) {
				unset( $this->terms[$type] );
			}
		}
	}

}
