<?php declare( strict_types = 1 );

namespace Wikibase\Lib\Tests\Store;

use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\TermIndexEntry;

/**
 * Mock implementation of MatchingTermsLookup.
 *
 * @license GPL-2.0-or-later
 */
class MockMatchingTermsLookup implements MatchingTermsLookup {

	/**
	 * @var TermIndexEntry[]
	 */
	protected array $terms;

	/**
	 * @param TermIndexEntry[] $terms
	 */
	public function __construct( array $terms ) {
		$this->terms = $terms;
	}

	/** @inheritDoc */
	public function getMatchingTerms(
		string $termText,
		string $entityType,
		$searchLanguage = null,
		$termType = null,
		array $options = []
	): array {
		$matchingTerms = [];
		foreach ( $this->terms as $term ) {
			if (
				$term->getEntityType() === $entityType
				&& ( $searchLanguage === null || in_array( $term->getLanguage(), (array)$searchLanguage ) )
				&& ( $termType === null || in_array( $term->getTermType(), (array)$termType ) )
				&& $this->textMatches( $termText, $term->getText(), $options )
			) {
				$matchingTerms[] = $term;
			}
		}

		$limit = $options['LIMIT'] > 0 ? $options['LIMIT'] : null;
		$offset = $options['OFFSET'] ?? 0;
		if ( $limit > 0 || $offset > 0 ) {
			$matchingTerms = array_slice( $matchingTerms, $offset, $limit );
		}

		return $matchingTerms;
	}

	private function textMatches( string $find, string $text, array $options = [] ): bool {
		if ( isset( $options[ 'caseSensitive' ] ) && $options[ 'caseSensitive' ] === false ) {
			$find = strtolower( $find );
			$text = strtolower( $text );
		}

		if ( isset( $options[ 'prefixSearch' ] ) && $options[ 'prefixSearch' ] === true ) {
			$text = substr( $text, 0, strlen( $find ) );
		}

		return $find === $text;
	}

}
