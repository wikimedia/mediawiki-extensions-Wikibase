<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\Formatters\CommonsLinkFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\CommonsLinkFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class CommonsLinkFormatterTest extends MediaWikiIntegrationTestCase {

	public function commonsLinkFormatProvider() {
		return [
			[
				new StringValue( 'example.jpg' ), // Lower-case file name
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example.jpg".*>.*example.jpg.*</a>@',
			],
			[
				new StringValue( 'Example.jpg' ),
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example.jpg".*>.*Example.jpg.*</a>@',
			],
			[
				new StringValue( 'Example space.jpg' ),
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example_space.jpg".*>.*Example space.jpg.*</a>@',
			],
			[
				new StringValue( 'Example_underscore.jpg' ),
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example_underscore.jpg".*>.*Example_underscore.jpg.*</a>@',
			],
			[
				new StringValue( 'Example+plus.jpg' ),
				'@<a .*href="//commons.wikimedia.org/wiki/File:Example%2Bplus.jpg".*>.*Example\+plus.jpg.*</a>@',
			],
			[
				new StringValue( '[[File:Invalid_title.mid]]' ),
				'@^\[\[File:Invalid_title.mid\]\]$@',
			],
			[
				new StringValue( '<a onmouseover=alert(0xF000)>ouch</a>' ),
				'@^&lt;a onmouseover=alert\(0xF000\)&gt;ouch&lt;/a&gt;$@',
			],
			[
				new StringValue( '' ),
				'@^$@',
			],
		];
	}

	/**
	 * @dataProvider commonsLinkFormatProvider
	 */
	public function testFormat( StringValue $value, $pattern, FormatterOptions $options = null ) {
		$formatter = new CommonsLinkFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertMatchesRegularExpression( $pattern, $html );
	}

	public function testFormatError() {
		$formatter = new CommonsLinkFormatter();
		$value = new NumberValue( 23 );

		$this->expectException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

}
