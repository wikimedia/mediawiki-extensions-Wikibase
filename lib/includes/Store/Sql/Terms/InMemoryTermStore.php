<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql\Terms;

/**
 * @license GPL-2.0-or-later
 */
class InMemoryTermStore implements TermInLangIdsAcquirer, TermInLangIdsResolver, TermStoreCleaner {

	/** @var int[][][] */
	private $terms = [];
	/** @var int */
	private $lastId = 0;

	public function acquireTermInLangIds( array $termsArray, $callback = null ): array {
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

	public function resolveTermInLangIds(
		array $termInLangIds,
		array $types = null,
		array $languages = null
	): array {
		if ( $termInLangIds === [] || $types === [] || $languages === [] ) {
			return [];
		}

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
					if ( in_array( $id, $termInLangIds ) ) {
						$terms[$type][$lang][] = $term;
					}
				}
			}
		}
		return $terms;
	}

	public function resolveGroupedTermInLangIds(
		array $groupedTermInLangIds,
		array $types = null,
		array $languages = null
	): array {
		return array_map(
			function ( $termInLangIdGroup ) use ( $types, $languages ) {
				return $this->resolveTermInLangIds( $termInLangIdGroup, $types, $languages );
			},
			$groupedTermInLangIds
		);
	}

	public function cleanTermInLangIds( array $termInLangIds ) {
		$termInLangIdsAsKeys = array_flip( $termInLangIds );
		foreach ( $this->terms as $type => &$termsOfType ) {
			foreach ( $termsOfType as $lang => &$termsOfLang ) {
				foreach ( $termsOfLang as $term => $id ) {
					if ( array_key_exists( $id, $termInLangIdsAsKeys ) ) {
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
