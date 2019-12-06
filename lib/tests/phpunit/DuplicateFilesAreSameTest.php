<?php

namespace Wikibase\Lib\Tests;

/**
 * Check that duplicated codes between Wikibase Lib and Wikibase submodules remain identical
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DuplicateFilesAreSameTest extends \PHPUnit\Framework\TestCase {

	public function duplicateFilesProvider() {
		$wikibaseLib = __DIR__ . '/../../resources/lib/';
		$wikibaseDataValueVW = __DIR__ . '/../../../view/lib/wikibase-data-values-value-view/lib/';

		return [
			"jqueryEventSpecialEachchange.js" => [
				$wikibaseLib . 'jquery.event/jquery.event.special.eachchange.js',
				$wikibaseDataValueVW . 'jquery.event/jquery.event.special.eachchange.js'
			],
			"jquery.ui.ooMenu.css" => [
				$wikibaseLib . 'jquery.ui/jquery.ui.ooMenu.css',
				$wikibaseDataValueVW . 'jquery.ui/jquery.ui.ooMenu.css'
			],
			"jquery.ui.ooMenu.js" => [
				$wikibaseLib . 'jquery.ui/jquery.ui.ooMenu.js',
				$wikibaseDataValueVW . 'jquery.ui/jquery.ui.ooMenu.js'
			],
			"jquery.ui.suggester.css" => [
				$wikibaseLib . 'jquery.ui/jquery.ui.suggester.css',
				$wikibaseDataValueVW . 'jquery.ui/jquery.ui.suggester.css'
			],
			"jquery.ui.suggester.js" => [
				$wikibaseLib . 'jquery.ui/jquery.ui.suggester.js',
				$wikibaseDataValueVW . 'jquery.ui/jquery.ui.suggester.js'
			],
			"jquery.util.getscrollbarwidth.js" => [
				$wikibaseLib . 'jquery.util/jquery.util.getscrollbarwidth.js',
				$wikibaseDataValueVW . 'jquery.util/jquery.util.getscrollbarwidth.js'
			],
			"util.highlightSubstring.js" => [
				$wikibaseLib . 'util/util.highlightSubstring.js',
				$wikibaseDataValueVW . 'util/util.highlightSubstring.js'
			],
			"util.inherit.js" => [
				$wikibaseLib . 'util/util.inherit.js',
				__DIR__ . '/../../../view/lib/wikibase-data-values/lib/util/util.inherit.js',
			],
		];
	}

	/**
	 * @dataProvider duplicateFilesProvider
	 */
	public function testFilesAreSame( $wikibaseFile, $wikibaseSubmoduleFile ) {
		$result = true;

		if ( filesize( $wikibaseFile ) == filesize( $wikibaseSubmoduleFile ) ) {
			$fpwbFile = fopen( $wikibaseFile, 'rb' );
			$fpwbsFile = fopen( $wikibaseSubmoduleFile, 'rb' );
			while ( !feof( $fpwbsFile ) && !feof( $fpwbFile ) ) {
				$contentwbFile = fread( $fpwbFile, 4096 );
				$contentwbsFile = fread( $fpwbsFile, 4096 );

				if ( $contentwbFile !== $contentwbsFile ) {
					fclose( $fpwbFile );
					fclose( $fpwbsFile );
					$result = $wikibaseFile . ' and ' . $wikibaseSubmoduleFile;
				}
			}
			fclose( $fpwbFile );
			fclose( $fpwbsFile );
		} else {
			$result = $wikibaseFile . ' and ' . $wikibaseSubmoduleFile;
		}
		$this->assertTrue(
			$result,
			"These files should be identical but they are different\nIf you added changes to one, please update the other\n"
		);
	}
}
