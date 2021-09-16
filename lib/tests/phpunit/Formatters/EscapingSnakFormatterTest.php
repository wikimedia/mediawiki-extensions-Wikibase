<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Formatters\EscapingSnakFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\EscapingSnakFormatter
 *
 * @group SnakFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EscapingSnakFormatterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @param string $output
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter( $output ) {
		$formatter = $this->createMock( SnakFormatter::class );

		$formatter->method( 'formatSnak' )
			->willReturn( $output );

		$formatter->method( 'getFormat' )
			->willReturn( SnakFormatter::FORMAT_PLAIN );

		return $formatter;
	}

	public function testFormatSnak() {
		$formatter = new EscapingSnakFormatter(
			SnakFormatter::FORMAT_HTML,
			$this->getSnakFormatter( '<foo>' ),
			'htmlspecialchars'
		);

		$p1 = new NumericPropertyId( 'P77' );
		$snak = new PropertyValueSnak( $p1, new StringValue( 'DUMMY' ) );
		$this->assertSame( '&lt;foo&gt;', $formatter->formatSnak( $snak ) );
	}

	public function testGetFormat() {
		$formatter = new EscapingSnakFormatter(
			'text/whatever',
			$this->getSnakFormatter( '<foo>' ),
			'htmlspecialchars'
		);

		$this->assertSame( 'text/whatever', $formatter->getFormat() );
	}

}
