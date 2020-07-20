<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\Lib\Formatters\CommonsThumbnailFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\CommonsThumbnailFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class CommonsThumbnailFormatterTest extends MediaWikiIntegrationTestCase {

	public function fileNameProvider() {
		$titleFormatter = MediaWikiServices::getInstance()->getTitleFormatter();
		$nsName = $titleFormatter->getNamespaceName( NS_FILE, 'foo' );

		return [
			[ '', '' ],
			[ 'A.jpg', '[[' . $nsName . ':A.jpg|frameless]]' ],
			[ 'File:A.jpg', '[[' . $nsName . ':A.jpg|frameless]]' ],
			[ '<INVALID>', '&#60;INVALID&#62;' ],
		];
	}

	/**
	 * @dataProvider fileNameProvider
	 */
	public function testFormat( $fileName, $expected ) {
		$value = new StringValue( $fileName );
		$formatter = new CommonsThumbnailFormatter();
		$this->assertSame( $expected, $formatter->format( $value ) );
	}

	public function testFormatError() {
		$formatter = new CommonsThumbnailFormatter();
		$this->expectException( InvalidArgumentException::class );
		$formatter->format( 'Image.jpg' );
	}

}
