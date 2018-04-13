<?php

namespace Wikibase\Lib\Tests\Formatters;

use ImageGalleryBase;
use MediaWikiTestCase;
use Title;
use Wikibase\Lib\Formatters\CommonsMediaImageGallery;

/**
 * @covers Wikibase\Lib\Formatters\CommonsMediaImageGallery
 *
 * @group ValueFormatters
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class CommonsMediaImageGalleryTest extends MediaWikiTestCase {

	public function testFactory() {
		$imageGallery = ImageGalleryBase::factory( 'wikibase-commons-media' );
		$this->assertInstanceOf(
			CommonsMediaImageGallery::class,
			$imageGallery
		);
	}

	public function testToHTML() {
		$imageGallery = ImageGalleryBase::factory( 'wikibase-commons-media' );
		$imageGallery->add( Title::newFromText( 'File:Example.jpg' ) );

		$html = $imageGallery->toHTML();

		$this->assertContains( 'width: auto', $html );
		$this->assertContains( 'Example.jpg', $html );
	}

}
