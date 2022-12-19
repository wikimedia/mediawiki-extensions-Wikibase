<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use MediaWikiCoversValidator;
use Message;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\Formatters\MessageSnakFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\MessageSnakFormatter
 * @uses Wikibase\DataModel\Entity\NumericPropertyId
 * @uses Wikibase\DataModel\Snak\PropertyNoValueSnak
 * @uses Wikibase\DataModel\Snak\PropertySomeValueSnak
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class MessageSnakFormatterTest extends \PHPUnit\Framework\TestCase {
	use MediaWikiCoversValidator;

	/**
	 * @param string $snakType
	 * @param string $format
	 *
	 * @return MessageSnakFormatter
	 */
	private function getFormatter( $snakType, $format ) {
		$message = $this->getMockBuilder( Message::class )
			->setConstructorArgs( [ 'message' ] )
			->getMock();

		foreach ( [ 'parse', 'text', 'plain' ] as $method ) {
			$message->method( $method )
				->willReturn( $method );
		}

		return new MessageSnakFormatter( $snakType, $message, $format );
	}

	public function testGetFormat() {
		$formatter = $this->getFormatter( 'any', 'test' );

		$this->assertEquals( 'test', $formatter->getFormat() );
	}

	/**
	 * @dataProvider snakProvider
	 */
	public function testFormatSnak_givenDifferentSnakTypes( Snak $snak, $expected ) {
		$formatter = $this->getFormatter( $snak->getType(), SnakFormatter::FORMAT_HTML );

		$this->assertEquals( $expected, $formatter->formatSnak( $snak ) );
	}

	public function snakProvider() {
		$id = new NumericPropertyId( 'P1' );

		return [
			[
				new PropertyValueSnak( $id, new StringValue( 'string' ) ),
				'parse',
			],
			[
				new PropertySomeValueSnak( $id ),
				'<span class="wikibase-snakview-variation-somevaluesnak">parse</span>',
			],
			[
				new PropertyNoValueSnak( $id ),
				'<span class="wikibase-snakview-variation-novaluesnak">parse</span>',
			],
		];
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormatSnak_givenDifferentFormats( $format, $expected ) {
		$snak = new PropertyValueSnak( new NumericPropertyId( 'P1' ), new StringValue( 'string' ) );
		$formatter = $this->getFormatter( $snak->getType(), $format );

		$this->assertEquals( $expected, $formatter->formatSnak( $snak ) );
	}

	public function formatProvider() {
		return [
			[ SnakFormatter::FORMAT_PLAIN, 'plain' ],
			[ SnakFormatter::FORMAT_WIKI, 'text' ],
			[ SnakFormatter::FORMAT_HTML, 'parse' ],
			[ SnakFormatter::FORMAT_HTML_DIFF, 'parse' ],
		];
	}

}
