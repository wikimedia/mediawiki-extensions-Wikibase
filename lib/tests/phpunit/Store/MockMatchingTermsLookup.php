<?php

namespace Wikibase\Lib\Tests\Store;

use Wikibase\Lib\Store\MatchingTermsLookup;
use Wikibase\Lib\Store\TermIndexSearchCriteria;
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
	protected $terms;

	/**
	 * @param TermIndexEntry[] $terms
	 */
	public function __construct( array $terms ) {
		$this->terms = $terms;
	}

	/**
	 * @note The $options parameters is ignored. The language to get is determined by the
	 * language of the first Term in $terms. $The termType and $entityType parameters are used,
	 * but the termType and entityType fields of the Terms in $terms are ignored.
	 *
	 * @param TermIndexSearchCriteria[] $criteria
	 * @param string|string[]|null $termType
	 * @param string|string[]|null $entityType
	 * @param array $options
	 *
	 * @return TermIndexEntry[]
	 */
	public function getMatchingTerms(
		array $criteria,
		$termType = null,
		$entityType = null,
		array $options = []
	) {
		$matchingTerms = [];

		$termType = $termType === null ? null : (array)$termType;
		$entityType = $entityType === null ? null : (array)$entityType;

		foreach ( $this->terms as $term ) {
			if ( ( $entityType === null || in_array( $term->getEntityType(), $entityType ) )
				&& ( $termType === null || in_array( $term->getTermType(), $termType ) )
				&& $this->termMatchesTemplates( $term, $criteria, $options )
			) {
				$matchingTerms[] = $term;
			}
		}

		$limit = $options['LIMIT'] ?? 0;

		if ( $limit > 0 ) {
			$matchingTerms = array_slice( $matchingTerms, 0, $limit );
		}

		return $matchingTerms;
	}

	/**
	 * @param TermIndexEntry $term
	 * @param TermIndexSearchCriteria[] $templates
	 * @param array $options
	 *
	 * @return bool
	 */
	private function termMatchesTemplates( TermIndexEntry $term, array $templates, array $options = [] ) {
		foreach ( $templates as $template ) {
			if ( $template->getTermType() !== null && $template->getTermType() !== $term->getTermType() ) {
				continue;
			}

			if ( $template->getLanguage() !== null && $template->getLanguage() !== $term->getLanguage() ) {
				continue;
			}

			if ( $template->getText() !== null && !$this->textMatches( $template->getText(), $term->getText(), $options ) ) {
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * @param string $find
	 * @param string $text
	 * @param array $options
	 *
	 * @return bool
	 */
	private function textMatches( $find, $text, array $options = [] ) {
		if ( isset( $options[ 'caseSensitive' ] ) && !$options[ 'caseSensitive' ] ) {
			$find = strtolower( $find );
			$text = strtolower( $text );
		}

		if ( isset( $options[ 'prefixSearch' ] ) && $options[ 'prefixSearch' ] ) {
			$text = substr( $text, 0, strlen( $find ) );
		}

		return $find === $text;
	}

}
