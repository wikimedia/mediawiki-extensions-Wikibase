<?php

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit_Framework_Assert;

/**
 * Utility class to load, normalize and compare N-Triples in RDF builder tests.
 *
 * @see https://en.wikipedia.org/wiki/N-Triples
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class NTriplesRdfTestHelper {

	/**
	 * @param string[]|string $nTriples
	 *
	 * @return string[] Sorted alphabetically.
	 */
	private function normalizeNTriples( $nTriples ) {
		if ( is_string( $nTriples ) ) {
			// Trim and ignore newlines at the end of the file only.
			$nTriples = explode( "\n", rtrim( $nTriples, "\n" ) );
		}

		sort( $nTriples );

		return $nTriples;
	}

	/**
	 * @param string[]|string $expected
	 * @param string[]|string $actual
	 * @param string $message
	 */
	public function assertNTriplesEquals( $expected, $actual, $message = '' ) {
		$expected = $this->normalizeNTriples( $expected );
		$actual = $this->normalizeNTriples( $actual );

		// Comparing $expected and $actual directly would show triples that are present in both but
		// shifted in position. That makes the output hard to read. Calculating the $missing and
		// $extra sets helps.
		$extra = array_diff( $actual, $expected );
		$missing = array_diff( $expected, $actual );

		// Cute: $missing and $extra can be equal only if they are empty. Comparing them here
		// directly looks a bit odd in code, but produces meaningful output, especially if the input
		// was sorted.
		PHPUnit_Framework_Assert::assertEquals( $missing, $extra, $message );
	}

}
