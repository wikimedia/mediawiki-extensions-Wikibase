<?php

namespace Wikibase\DataAccess\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @coversNothing
 */
class NoReverseDependencyTest extends \PHPUnit\Framework\TestCase {

	public function testNoClientDependency() {
		$this->assertSame( [], $this->getFilesContainingString( 'Wikibase\\Client\\', __DIR__ . '/../../src/' ) );
	}

	public function testNoRepoDependency() {
		$this->assertSame( [], $this->getFilesContainingString( 'Wikibase\\Repo\\', __DIR__ . '/../../src/' ) );
	}

	/**
	 * @param string $string
	 * @param string $dir
	 *
	 * @return string[]
	 */
	private function getFilesContainingString( $string, $dir ) {
		$paths = [];
		$directoryIterator = new RecursiveDirectoryIterator( $dir );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( $fileInfo->isFile() && substr( $fileInfo->getFilename(), -4 ) === '.php' ) {
				$text = file_get_contents( $fileInfo->getPathname() );
				$text = preg_replace( '@/\*.*?\*/@s', '', $text );

				if ( strpos( $text, $string ) !== false ) {
					$paths[] = str_replace( $dir, '', $fileInfo->getPathname() );
				}
			}
		}
		sort( $paths );
		return $paths;
	}

}
