<?php

namespace Wikibase\Repo\Tests\Rdf;

use InvalidArgumentException;
use LogicException;
use PHPUnit_Framework_Assert;
use PHPUnit_Framework_AssertionFailedError;

/**
 * Utility class to normalize and compare N-Triples in RDF builder tests.
 *
 * @see https://en.wikipedia.org/wiki/N-Triples
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class NTriplesRdfTestHelper {

	/**
	 * @var RdfBuilderTestData|null
	 */
	private $testData;

	/**
	 * @var bool
	 */
	private $forceActual = false;

	public function __construct( RdfBuilderTestData $testData = null ) {
		$this->testData = $testData;
	}

	/**
	 * @return boolean whether expected data will be overwritten by actual data.
	 */
	public function isForceActual() {
		return $this->forceActual;
	}

	/**
	 * Setting this to true allows expected to be updated from actual data, in situations where
	 * the actual data is known to be correct. This may be helpful for updating test data after
	 * changing the RDF mapping.
	 *
	 * @param boolean $forceActual If set to true, expected data will be overwritten by actual data.
	 */
	public function setForceActual( $forceActual ) {
		$this->forceActual = $forceActual;
	}

	/**
	 * @return RdfBuilderTestData|null
	 */
	public function getTestData() {
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

	/**
	 * @param string|string[] $dataSetName
	 * @param string|string[] $actual
	 * @param string $message
	 */
	public function assertNTriplesEqualsDataset( $dataSetName, $actual, $message = null ) {
		if ( !$this->testData ) {
			throw new LogicException( 'No RdfBuilderTestData provided to constructor' );
		}

		$names = (array)$dataSetName;
		$prettyName = join( '+', $names );

		if ( count( $names ) === 1 ) {
			$singleDataSet = reset( $names );
		} else {
			$singleDataSet = false;
		}

		if ( $singleDataSet && !$this->testData->hasDataSet( $singleDataSet ) ) {
			$this->testData->putTestData( $singleDataSet, $actual, '.actual' );

			PHPUnit_Framework_Assert::fail(
				"Data set $singleDataSet not found!"
				. " Created file with the current data with the suffix .actual"
			);
		}

		if ( $message === null ) {
			$message = "Data set $prettyName";
		}

		$expected = $this->testData->getNTriples( $names );

		try {
			$this->assertNTriplesEquals( $expected, $actual, $message );
		} catch ( PHPUnit_Framework_AssertionFailedError $ex ) {
			if ( $this->forceActual && $singleDataSet ) {
				$this->testData->putTestData( $singleDataSet, $actual );
				PHPUnit_Framework_Assert::fail( "Updated data set $singleDataSet!\n" );
			} else {
				throw $ex;
			}
		}
	}

}
