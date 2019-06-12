<?php

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryTermIdsStore implements TermIdsAcquirer, TermIdsResolver, TermIdsCleaner {

	private $terms = [];
	private $lastId = 0;

	public function acquireTermIds( array $termsArray, $callback = null ): array {
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

		if ( $callback !== null ) {
			( $callback )( $ids );
		}

		return $ids;
	}

	public function resolveTermIds(
		array $termIds,
		array $types = null,
		array $languages = null
	): array {
		$terms = [];
		foreach ( $this->terms as $type => $termsOfType ) {
			if ( $types && !in_array( $type, $types ) ) {
				continue;
			}

			foreach ( $termsOfType as $lang => $termsOfLang ) {
				if ( $languages && !in_array( $lang, $languages ) ) {
					continue;
				}

				foreach ( $termsOfLang as $term => $id ) {
					if ( in_array( $id, $termIds ) ) {
						$terms[$type][$lang][] = $term;
					}
				}
			}
		}
		return $terms;
	}

	public function resolveGroupedTermIds(
		array $groupedTermIds,
		array $types = null,
		array $languages = null
	): array {
		return array_map(
			function ( $termIdGroup ) use ( $types, $languages ) {
				return $this->resolveTermIds( $termIdGroup, $types, $languages );
			},
			$groupedTermIds
		);
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
