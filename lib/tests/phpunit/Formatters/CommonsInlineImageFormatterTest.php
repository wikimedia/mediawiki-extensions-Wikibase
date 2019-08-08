<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use MediaWikiTestCase;
use ParserOptions;
use Wikibase\Lib\Formatters\CommonsInlineImageFormatter;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\CommonsInlineImageFormatter
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
		$fileUrl = '.*//upload\.wikimedia\.org/wikipedia/commons/.*/120px-Example\.jpg';
		$pageUrl = 'https://commons\.wikimedia\.org/wiki/File:Example\.jpg';
		$exampleJpgHtmlRegex = '@<div .*<a[^>]+href="' . $pageUrl . '"[^>]*>' .
				'<img.*src="' . $fileUrl . '".*/></a></div>.*' .
				'<div .*><a[^>]+href="' . $pageUrl . '"[^>]*>Example\.jpg</a>.*\d+.*</div>@s';

		return [
			[
				new StringValue( 'example.jpg' ), // Lower-case file name
				$exampleJpgHtmlRegex
			],
			[
				new StringValue( 'Example.jpg' ),
				$exampleJpgHtmlRegex
			],
			[
				new StringValue( 'Example-That-Does-Not-Exist.jpg' ),
				'@^.*<a[^>]+href="https://commons.wikimedia.org/wiki/File:Example-That-Does-Not-Exist.jpg"[^>]*>@s'
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
		if ( $shouldContain && !wfFindFile( 'Example.jpg' ) ) {
			$this->markTestSkipped( '"Example.jpg" not found? Instant commons disabled?' );
		}

		$parserOptions = new ParserOptions();
		$parserOptions->setThumbSize( 0 );
		$thumbLimits = [ 0 => 120 ];

		$formatter = new CommonsInlineImageFormatter(
			$parserOptions,
			$thumbLimits,
			$this->newFormatterOptions()
		);

		$html = $formatter->format( $value );
		if ( $shouldContain ) {
			$this->assertRegExp( $pattern, $html );
		} else {
			$this->assertNotRegExp( $pattern, $html );
		}
	}

	public function testFormatError() {
		$thumbLimits = [ 0 => 120 ];
		$formatter = new CommonsInlineImageFormatter(
			new ParserOptions(),
			$thumbLimits,
			$this->newFormatterOptions()
		);
		$value = new NumberValue( 23 );

		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

	private function newFormatterOptions() {
		$options = [
			ValueFormatter::OPT_LANG => 'en'
		];

		return new FormatterOptions( $options );
	}

}
