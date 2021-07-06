<?php

namespace Wikibase\Lib\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NoBadDependencyUsageTest extends \PHPUnit\Framework\TestCase {

	public function testNoBadUsageInLib() {
		// Increasing these allowances is forbidden
		$this->assertStringsNotInLib(
			[
				// MediaWiki RDBMS – use DomainDb instead
				'LoadBalancer' => 10,
				'LBFactory' => 13,
				'wfGetDB' => 0,
				'wfGetLB' => 0,
				// references to repo or client
				'WikibaseRepo::' => 1,
				'Wikibase\\Repo\\' => 1,
				'Wikibase\\\\Repo\\\\' => 0,
				'WikibaseClient::' => 2,
				'Wikibase\\Client\\' => 2,
				'Wikibase\\\\Client\\\\' => 0,
			]
		);
	}

	/**
	 * @param int[] $stringCounts Keys of strings and values of number of allowed occurrences
	 */
	private function assertStringsNotInLib( $stringCounts ) {
		$counts = $this->countMultiStringInDir( array_keys( $stringCounts ), __DIR__ . '/../../' );
		foreach ( $stringCounts as $string => $maxAllowance ) {
			$this->assertLessThanOrEqual(
				$maxAllowance,
				$counts[$string],
				'You are not allowed to use ' . $string . ' in this component'
			);
			$this->assertThat(
				$maxAllowance,
				$this->logicalNot( $this->greaterThan( $counts[$string] ) ),
				'It looks like you successfully reduced the usage of ' .
				$string . ' in this component. Congratulations :) ' .
				'Please lower the threshold in NoBadDependencyUsageTest accordingly, ' .
				'so that no new usages are accidentally introduced in the future.'
			);
		}
	}

	/**
	 * @param string[] $strings
	 * @param string $dir
	 *
	 * @return int[] counts indexed by string
	 */
	private function countMultiStringInDir( $strings, $dir ) {
		$counts = [];
		foreach ( $strings as $string ) {
			$counts[$string] = 0;
		}

		$directoryIterator = new RecursiveDirectoryIterator( $dir );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( $directoryIterator, RecursiveIteratorIterator::SELF_FIRST ) as $fileInfo ) {
			if ( !$fileInfo->isFile() ) {
				continue;
			}
			if ( $fileInfo->getExtension() !== 'php' ) {
				continue;
			}
			$path = $fileInfo->getRealPath();
			if ( $path === __FILE__ ) {
				continue;
			}

			$text = file_get_contents( $path );
			$text = preg_replace( '@/\*.*?\*/@s', '', $text );
			$text = preg_replace( '@//.*$@m', '', $text );

			foreach ( $strings as $string ) {
				if ( strpos( $text, $string ) !== false ) {
					$counts[$string]++;
				}
			}
		}

		return $counts;
	}

}
