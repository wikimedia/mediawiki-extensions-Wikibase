<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\EscapingSnakFormatter;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\EscapingSnakFormatter
 *
 * @group SnakFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EscapingSnakFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param string $output
	 *
	 * @return SnakFormatter
	 */
	private function getSnakFormatter( $output ) {
		$formatter = $this->getMock( SnakFormatter::class );

		$formatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( $output ) );

		$formatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( SnakFormatter::FORMAT_PLAIN ) );

		return $formatter;
	}

	public function testFormatSnak() {
		$formatter = new EscapingSnakFormatter(
			SnakFormatter::FORMAT_HTML,
			$this->getSnakFormatter( '<foo>' ),
			'htmlspecialchars'
		);

		$p1 = new PropertyId( 'P77' );
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
