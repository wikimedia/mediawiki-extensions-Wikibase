<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\Lib\Formatters\CommonsThumbnailFormatter;

/**
 * @covers Wikibase\Lib\CommonsThumbnailFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class CommonsThumbnailFormatterTest extends \MediaWikiTestCase {

	public function fileNameProvider() {
		return [
			[ '', '' ],
			[ 'A.jpg', '[[File:A.jpg|frameless]]' ],
			[ 'File:A.jpg', '[[File:A.jpg|frameless]]' ],
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
		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( 'Image.jpg' );
	}

}
