<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\NumberValue;
use DataValues\QuantityValue;
use DataValues\UnboundedQuantityValue;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use ValueFormatters\BasicNumberLocalizer;
use ValueFormatters\NumberLocalizer;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\QuantityDetailsFormatter;

/**
 * @covers Wikibase\Lib\QuantityDetailsFormatter
 *
 * @group ValueFormatters
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class QuantityDetailsFormatterTest extends PHPUnit_Framework_TestCase {

	private function newFormatter( NumberLocalizer $numberLocalizer = null ) {
		$vocabularyUriFormatter = $this->getMock( ValueFormatter::class );
		$vocabularyUriFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnCallback( function( $value ) {
				return preg_match( '@^http://www\.wikidata\.org/entity/(.*)@', $value, $matches )
					? $matches[1]
					: $value;
			} ) );

		return new QuantityDetailsFormatter(
			$numberLocalizer ?: new BasicNumberLocalizer(),
			$vocabularyUriFormatter
		);
	}

	/**
	 * @dataProvider quantityFormatProvider
	 */
	public function testFormat( $value, $pattern ) {
		$formatter = $this->newFormatter();

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		return [
			[
				QuantityValue::newFromNumber( '+5', '1', '+6', '+4' ),
				'@' . implode( '.*',
					[
						'<h4[^<>]*>[^<>]*\b5\b[^<>]*1[^<>]*</h4>',
						'<td[^<>]*>[^<>]*\b5\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b6\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b4\b[^<>]*</td>',
						'<td[^<>]*>[^<>]*\b1\b[^<>]*</td>',
					]
				) . '@s'
			],
			[
				UnboundedQuantityValue::newFromNumber( '+5', '1' ),
				'@<h4[^<>]*>5</h4>'
					. '.*<td[^<>]*>5</td>'
					. '.*<td[^<>]*>1</td>@s'
			],
			'Unit 1' => [
				QuantityValue::newFromNumber( '+5', '1', '+6', '+4' ),
				'@<td class="wb-quantity-unit">1</td>@'
			],
			'Non-URL' => [
				QuantityValue::newFromNumber( '+5', 'Ultrameter', '+6', '+4' ),
				'@<td class="wb-quantity-unit">Ultrameter</td>@'
			],
			'Item ID' => [
				QuantityValue::newFromNumber( '+5', 'Q1', '+6', '+4' ),
				'@<td class="wb-quantity-unit">Q1</td>@'
			],
			'Local URL' => [
				QuantityValue::newFromNumber( '+5', 'http://localhost/repo/Q11573', '+6', '+4' ),
				'@<td class="wb-quantity-unit">http://localhost/repo/Q11573</td>@'
			],
			'External URL' => [
				QuantityValue::newFromNumber( '+5', 'https://en.wikipedia.org/wiki/Unitless', '+6', '+4' ),
				'@<td class="wb-quantity-unit">https://en\.wikipedia\.org/wiki/Unitless</td>@'
			],
			'Wikidata wiki URL' => [
				QuantityValue::newFromNumber( '+5', 'https://www.wikidata.org/wiki/Q11573', '+6', '+4' ),
				'@<td class="wb-quantity-unit">https://www\.wikidata\.org/wiki/Q11573</td>@'
			],
			'Wikidata concept URI' => [
				QuantityValue::newFromNumber( '+5', 'http://www.wikidata.org/entity/Q11573', '+6', '+4' ),
				'@<td class="wb-quantity-unit"><a href="http://www\.wikidata\.org/entity/Q11573">Q11573</a></td>@'
			],
			'HTML injection' => [
				QuantityValue::newFromNumber( '+5', '<a>m</a>', '+6', '+4' ),
				'@\b5 &lt;a&gt;m&lt;/a&gt;@'
			],
		];
	}

	public function testGivenHtmlCharacters_formatEscapesHtmlCharacters() {
		$unitFormatter = $this->getMock( NumberLocalizer::class );
		$unitFormatter->expects( $this->any() )
			->method( 'localizeNumber' )
			->will( $this->returnValue( '<a>+2</a>' ) );

		$formatter = $this->newFormatter( $unitFormatter );
		$value = QuantityValue::newFromNumber( '+2', '<a>m</a>', '+2', '+2' );

		$html = $formatter->format( $value );
		$this->assertNotContains( '<a>', $html );
		$this->assertContains( '&lt;a&gt;', $html );
		$this->assertNotContains( '&amp;', $html );
	}

	public function testFormatError() {
		$formatter = $formatter = $this->newFormatter();
		$value = new NumberValue( 23 );

		$this->setExpectedException( InvalidArgumentException::class );
		$formatter->format( $value );
	}

}
