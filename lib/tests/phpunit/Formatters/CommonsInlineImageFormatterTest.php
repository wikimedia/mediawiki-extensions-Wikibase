<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiTestCase;
use Wikibase\Lib\Formatters\CommonsInlineImageFormatter;

/**
 * @covers Wikibase\Lib\Formatters\CommonsInlineImageFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Marius Hoch
 */
class CommonsInlineImageFormatterTest extends MediaWikiTestCase {

	public function commonsInlineImageFormatterProvider() {
		return [
			[
				new StringValue( 'example.jpg' ), // Lower-case file name
				'@<ul .*<img.*src=".*//upload.wikimedia.org/wikipedia/commons/.*/Example.jpg".*/>.*Example.jpg.*</ul>@s'
			],
			[
				new StringValue( 'Example.jpg' ),
				'@<ul .*<img.*src=".*//upload.wikimedia.org/wikipedia/commons/.*/Example.jpg".*/>.*Example.jpg.*</ul>@s'
			],
			[
				new StringValue( 'Example-That-Does-Not-Exist.jpg' ),
				'@^.*<a.*href=".*Example-That-Does-Not-Exist.jpg.*@s'
			],
			[
				new StringValue( 'Dangerous-quotes""' ),
				'@/""/@s',
				false
			],
			[
				new StringValue( '<eviltag>' ),
				'@/<eviltag>/@s',
				false
			],
		];
	}

	/**
	 * @dataProvider commonsInlineImageFormatterProvider
	 */
	public function testFormat( StringValue $value, $pattern, $shouldContain = true ) {
		global $wgUseInstantCommons;

		if ( $shouldContain && !$wgUseInstantCommons ) {
			$this->markTestSkipped( '$wgUseInstantCommons needed' );
		}

		$formatter = new CommonsInlineImageFormatter();

		$html = $formatter->format( $value );
		if ( $shouldContain ) {
			$this->assertRegExp( $pattern, $html );
		} else {
			$this->assertNotRegExp( $pattern, $html );
		}
	}

	public function testFormatError() {
		$formatter = new CommonsInlineImageFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

}
