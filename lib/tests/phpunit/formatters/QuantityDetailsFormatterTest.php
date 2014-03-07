<?php

namespace Wikibase\Lib\Test;

use DataValues\NumberValue;
use DataValues\QuantityValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\QuantityDetailsFormatter;

/**
 * @covers Wikibase\Lib\QuantityDetailsFormatter
 *
 * @group ValueFormatters
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class QuantityDetailsFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider quantityFormatProvider
	 *
	 * @covers QuantityDetailsFormatter::format
	 */
	public function testFormat( $value, $options, $pattern ) {
		$formatter = new QuantityDetailsFormatter( $options );

		$html = $formatter->format( $value );
		$this->assertRegExp( $pattern, $html );
	}

	public function quantityFormatProvider() {
		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => 'en'
		) );

		return array(
			array(
				QuantityValue::newFromNumber( '+5', '1', '+6', '+4' ),
				$options,
				'@' . implode( '.*',
					array(
						'<dd[^<>]*>[^<>]*5[^<>]*</dd>',
						'<dd[^<>]*>[^<>]*6[^<>]*</dd>',
						'<dd[^<>]*>[^<>]*4[^<>]*</dd>',
						'<dd[^<>]*>[^<>]*1[^<>]*</dd>',
					)
				) . '@s'
			),
		);
	}

	/**
	 * @covers QuantityDetailsFormatter::format
	 */
	public function testFormatError() {
		$formatter = new QuantityDetailsFormatter( new FormatterOptions() );
		$value = new NumberValue( 23 );

		$this->setExpectedException( 'InvalidArgumentException' );
		$formatter->format( $value );
	}
}
