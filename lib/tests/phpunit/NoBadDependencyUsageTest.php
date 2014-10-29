<?php

namespace Wikibase\Lib\Test;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @group WikibaseLib
 * @group Wikibase
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NoBadDependencyUsageTest extends \PHPUnit_Framework_TestCase {

	public function testNoRepoUsageInLib() {
		// Increasing this allowance is forbidden
		$this->assertStringNotInLib( 'WikibaseRepo' . '::', 4 );
		$this->assertStringNotInLib( 'Wikibase\\Repo\\', 4 );
	}

	public function testNoClientUsageInLib() {
		// Increasing this allowance is forbidden
		$this->assertStringNotInLib( 'WikibaseClient' . '::', 3 );
		$this->assertStringNotInLib( 'Wikibase\\Client\\', 3 );
	}

	private function assertStringNotInLib( $string, $maxAllowance ) {
		$this->assertStringNotInDir(
			$string,
			__DIR__ . '/../../',
			$maxAllowance
		);
	}

	private function assertStringNotInDir( $string, $dirs, $maxAllowance ) {
		$dirs = (array)$dirs;

		foreach ( $dirs as $dir ) {
			$this->assertLessThanOrEqual(
				$maxAllowance,
				$this->countStringInDir( $string, $dir ),
				'You are not allowed to use ' . $string . ' in this component'
			);
		}
	}

	private function countStringInDir( $string, $dir ) {
		$count = 0;
		$directoryIterator = new RecursiveDirectoryIterator( $dir );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( $fileInfo->isFile() && substr( $fileInfo->getFilename(), -4 ) === '.php' ) {
				$text = file_get_contents( $fileInfo->getPathname() );
				$text = preg_replace( '@/\*.*?\*/@s', '', $text );

				if ( stripos( $text, $string ) !== false ) {
					$count++;
				}
			}
		}

		return $count;
	}

}
