<?php

namespace Wikibase\DataAccess\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class NoReverseDependencyTest extends \PHPUnit_Framework_TestCase {

	// TODO: Make it impossible to use a class from Wikibase\\Client\\ once the only dependency is removed
	public function testNoClientDependency() {
		$this->assertEquals(
			[
				'RepositoryServiceContainer.php',
				'RepositoryServiceContainerFactory.php',
				'RepositoryServiceWiring.php',
			],
			$this->getFilesContainingString( 'Wikibase\\Client\\', __DIR__ . '/../../src/' )
		);
	}

	public function testNoRepoDependency() {
		$this->assertEmpty( $this->getFilesContainingString( 'Wikibase\\Repo\\', __DIR__ . '/../../src/' ) );
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

		return $paths;
	}

}