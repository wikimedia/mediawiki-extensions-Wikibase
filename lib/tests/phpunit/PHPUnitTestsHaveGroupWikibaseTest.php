<?php

namespace Wikibase\Lib\Tests;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Structure test making sure all PHPUnit tests within Wikibase have "@group Wikibase" set.
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @coversNothing
 */
class PHPUnitTestsHaveGroupWikibaseTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider dirProvider
	 */
	public function testTestsHaveGroup( $dir ) {
		$files = $this->getTestFilesWithoutGroup(
			'Wikibase',
		   __DIR__ . '/../../../' . $dir . '/tests/phpunit'
		);

		$this->assertSame(
			[],
			$files,
			'All Wikibase ' . $dir . ' PHPUnit tests should have "@group Wikibase" on them.'
		);
	}

	public function dirProvider() {
		return [
			[ 'client' ],
			[ 'data-access' ],
			[ 'lib' ],
			[ 'repo' ],
			[ 'repo/rest-api' ],
			[ 'view' ],
		];
	}

	/**
	 * @param string $string
	 * @param string $dir
	 *
	 * @return string[]
	 */
	private function getTestFilesWithoutGroup( $string, $dir ) {
		$pattern = '/@group ' . preg_quote( $string, '/' ) . '\b/';
		$files = [];
		$directoryIterator = new RecursiveDirectoryIterator( $dir );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( $fileInfo->isFile() && substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$text = file_get_contents( $fileInfo->getPathname() );

				if ( strpos( $text, 'abstract class' ) !== false ) {
					// Ignore abstract base classes.
					continue;
				}

				if ( preg_match( $pattern, $text ) === 0 ) {
					$files[] = $fileInfo->getPathname();
				}
			}
		}

		return $files;
	}

}
