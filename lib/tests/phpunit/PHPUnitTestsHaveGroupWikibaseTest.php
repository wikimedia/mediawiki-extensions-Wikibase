<?php

namespace Wikibase\Lib\Test;

use PHPUnit_Framework_TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Structure test making sure all PHPUnit tests within Wikibase have "@group Wikibase" set.
 *
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PHPUnitTestsHaveGroupWikibaseTest extends PHPUnit_Framework_TestCase {

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
			[ 'lib' ],
			[ 'repo' ],
			[ 'view' ],
		];
	}

	private function getTestFilesWithoutGroup( $string, $dir ) {
		$string = '@group ' . $string;
		$files = [];
		$directoryIterator = new RecursiveDirectoryIterator( $dir );

		/**
		 * @var SplFileInfo $fileInfo
		 */
		foreach ( new RecursiveIteratorIterator( $directoryIterator ) as $fileInfo ) {
			if ( $fileInfo->isFile() && substr( $fileInfo->getFilename(), -8 ) === 'Test.php' ) {
				$text = file_get_contents( $fileInfo->getPathname() );
				if ( stripos( $text, 'abstract class' ) !== false ) {
					// Ignore abstract base classes.
					continue;
				}

				if ( preg_match( '@' . preg_quote( $string, '@' ) . '[^\w]@i', $text ) === 0 ) {
					$files[] = $fileInfo->getPathname();
				}
			}
		}

		return $files;
	}

}
