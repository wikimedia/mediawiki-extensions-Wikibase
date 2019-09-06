<?php

namespace Wikibase\Repo\Tests\Rdf;

use PHPUnit\Framework\Assert;
use RuntimeException;

/**
 * Utility class to normalize and compare N-Triples in RDF builder tests.
 *
 * @see https://en.wikipedia.org/wiki/N-Triples
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class NTriplesRdfTestHelper {

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData;

	/**
	 * @var bool
	 */
	private $allBlanksEqual = false;

	public function __construct( RdfBuilderTestData $testData = null ) {
		$this->testData = $testData;
	}

	/**
	 * @return boolean whether all blank nodes are considered equal
	 */
	public function getAllBlanksEqual() {
		return $this->allBlanksEqual;
	}

	/**
	 * Setting all blank nodes to be equal allows tests to be robust against changes in the
	 * numbering of blank nodes. However, it also means that no test can reöly on the identity
	 * of a blank node.
	 *
	 * @param boolean $allBlanksEqual whether all blank nodes are considered equal
	 */
	public function setAllBlanksEqual( $allBlanksEqual ) {
		$this->allBlanksEqual = $allBlanksEqual;
	}

	/**
	 * @return RdfBuilderTestData|null
	 */
	public function getTestData() {
		if ( !$this->testData ) {
			throw new RuntimeException( 'No RdfBuilderTestData provided to constructor' );
		}

		return $this->testData;
	}

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

		if ( $this->allBlanksEqual ) {
			$nTriples = array_map(
				function ( $line ) {
					return preg_replace( '/_:\w+/', '_:#####', $line );
				},
				$nTriples
			);
		}

		$nTriples = array_unique( $nTriples );
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
		Assert::assertEquals( $missing, $extra, $message );
	}

	/**
	 * Compares the actual data to the expected data, and records any differences by creating
	 * new files in the test data directory, with the suffixes .extra and .missing.
	 *
	 * @note This method is intended to be used when updating test data, or manually
	 * investigating test failures. Code using this method is typically not checked in.
	 *
	 * @param string|string[] $dataSetNames
	 * @param string|string[] $actual
	 */
	public function recordNTriplesDatasetDifferences( $dataSetNames, $actual ) {
		$testData = $this->getTestData();

		$dataSetNames = (array)$dataSetNames;
		$joinedName = implode( '-', $dataSetNames );

		$expected = $testData->getNTriples( ...$dataSetNames );
		$expected = $this->normalizeNTriples( $expected );
		$actual = $this->normalizeNTriples( $actual );

		// Comparing $expected and $actual directly would show triples that are present in both but
		// shifted in position. That makes the output hard to read. Calculating the $missing and
		// $extra sets helps.
		$extra = array_diff( $actual, $expected );
		$missing = array_diff( $expected, $actual );

		if ( !empty( $extra ) ) {
			$testData->putTestData( $joinedName, $extra, '.extra' );
		}

		if ( !empty( $missing ) ) {
			$testData->putTestData( $joinedName, $missing, '.missing' );
		}
	}

	/**
	 * Creates a test data file from the given triples. The file will be created in the
	 * test data directory.
	 *
	 * @note This method is intended to be used when updating test data, or manually
	 * investigating test failures. Code using this method is typically not checked in.
	 *
	 * @param string|string[] $dataSetNames
	 * @param string|string[] $triples
	 * @param string $suffix File name suffix, including the leading dot. None per default.
	 */
	public function createNTriplesDataset( $dataSetNames, $triples, $suffix = '' ) {
		$testData = $this->getTestData();

		$dataSetNames = (array)$dataSetNames;
		$joinedName = implode( '-', $dataSetNames );

		$testData->putTestData( $joinedName, $triples, $suffix );
	}

	/**
	 * @param string|string[] $dataSetNames
	 * @param string|string[] $actual
	 * @param string $message
	 */
	public function assertNTriplesEqualsDataset( $dataSetNames, $actual, $message = null ) {
		$testData = $this->getTestData();

		$dataSetNames = (array)$dataSetNames;
		$prettyName = implode( '+', $dataSetNames );

		if ( $message === null ) {
			$message = "Data set $prettyName";
		}

		$expected = $testData->getNTriples( ...$dataSetNames );
		$this->assertNTriplesEquals( $expected, $actual, $message );
	}

}
