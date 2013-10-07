<?php

namespace Wikibase\Lib\Test;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @group WikibaseLib
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class NoClientOrRepoUsageTest extends \PHPUnit_Framework_TestCase {

	public function testNoRepoUsage() {
		// Increasing this allowance is forbidden
		$this->assertStringNotInLib( 'WikibaseRepo' . '::', 4 );
		$this->assertStringNotInLib( 'Wikibase\\Repo\\', 4 );
	}

	public function testNoClientUsage() {
		// Increasing this allowance is forbidden
		$this->assertStringNotInLib( 'WikibaseClient' . '::', 3 );
		$this->assertStringNotInLib( 'Wikibase\\Client\\', 5 );
	}

	public function assertStringNotInLib( $string, $maxAllowance = 0 ) {
		$dirs = array(
			__DIR__ . '/../../'
		);

		foreach ( $dirs as $dir ) {
			$this->assertLessThanOrEqual(
				$maxAllowance,
				$this->countStringInDir( $string, $dir ),
				'You are not allowed to use Repo or Client code in Lib!'
			);
		}
	}

	public function countStringInDir( $string, $dir ) {
		$count = 0;
		$directoryIterator = new RecursiveDirectoryIterator( $dir );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( $fileInfo->isFile() && substr( $fileInfo->getFilename(), -4 ) === '.php' ) {
				if ( stripos( file_get_contents( $fileInfo->getPathname() ), $string ) !== false ) {
					$count++;
				}
			}
		}

		return $count;
	}

}
