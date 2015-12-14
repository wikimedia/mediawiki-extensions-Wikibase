<?php

namespace Wikibase\Test\Rdf;

use PHPUnit_Framework_TestCase;

/**
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class RdfTestBase extends PHPUnit_Framework_TestCase {

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData = null;

	/**
	 * @return RdfBuilderTestData
	 */
	protected function getTestData() {
		if ( $this->testData === null ) {
			$class = get_called_class();
			// Strip the path and the "Test" suffix from the full qualified class name.
			$class = substr( $class, strrpos( $class, '\\' ) + 1, -4 );

			$this->testData = new RdfBuilderTestData(
				__DIR__ . '/../../data/rdf',
				__DIR__ . '/../../data/rdf/' . $class
			);
		}

		return $this->testData;
	}

	/**
	 * @param string|string[] $data
	 *
	 * @return string[] Sorted alphabetically.
	 */
	private function normalizeNTriples( $data ) {
		if ( is_string( $data ) ) {
			// Only trim and ignore newlines at the end of the file.
			$data = explode( "\n", rtrim( $data, "\n" ) );
		}

		sort( $data );

		return $data;
	}

	/**
	 * @param string|string[] $expected
	 * @param string|string[] $actual
	 * @param string $message
	 */
	protected function assertNTriplesEquals( $expected, $actual, $message = '' ) {
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
		$this->assertEquals( $missing, $extra, $message );
	}

}
