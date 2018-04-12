<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\Lib\DispatchingSnakFormatter;
use Wikibase\Lib\MessageSnakFormatter;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Lib\DispatchingSnakFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DispatchingSnakFormatterTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @param string $dataType
	 *
	 * @return PropertyDataTypeLookup
	 */
	private function getDataTypeLookup( $dataType = 'string' ) {
		$dataTypeLookup = $this->getMock( PropertyDataTypeLookup::class );

		$dataTypeLookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( $dataType ) );

		return $dataTypeLookup;
	}

	/**
	 * @param string $output the return value for formatSnak
	 * @param string $format the return value for getFormat
	 *
	 * @return SnakFormatter
	 */
	private function makeSnakFormatter( $output, $format = SnakFormatter::FORMAT_PLAIN ) {
		$formatter = $this->getMock( SnakFormatter::class );

		$formatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( $output ) );

		$formatter->expects( $this->any() )
			->method( 'getFormat' )
			->will( $this->returnValue( $format ) );

		return $formatter;
	}

	/**
	 * @dataProvider constructorProvider
	 */
	public function testConstructor( $format, array $formattersBySnakType, array $formattersByDataType ) {
		$dataTypeLookup = $this->getDataTypeLookup();

		new DispatchingSnakFormatter(
			$format,
			$dataTypeLookup,
			$formattersBySnakType,
			$formattersByDataType
		);

		// we are just checking that the constructor did not throw an exception
		$this->assertTrue( true );
	}

	public function constructorProvider() {
		$formatter = new MessageSnakFormatter(
			'novalue',
			wfMessage( 'wikibase-snakview-snaktypeselector-novalue' ),
			SnakFormatter::FORMAT_HTML_DIFF
		);

		return [
			'plain constructor call' => [
				SnakFormatter::FORMAT_HTML_DIFF,
				[ 'novalue' => $formatter ],
				[ 'string' => $formatter ],
			],
			'constructor call with formatters for base format ID' => [
				SnakFormatter::FORMAT_HTML,
				[ 'novalue' => $formatter ],
				[ 'string' => $formatter ],
			],
		];
	}

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( $format, array $formattersBySnakType, array $formattersByDataType ) {
		$this->setExpectedException( InvalidArgumentException::class );

		$dataTypeLookup = $this->getDataTypeLookup();

		new DispatchingSnakFormatter(
			$format,
			$dataTypeLookup,
			$formattersBySnakType,
			$formattersByDataType
		);
	}

	public function constructorErrorsProvider() {
		$formatter = new MessageSnakFormatter(
			'novalue',
			wfMessage( 'wikibase-snakview-snaktypeselector-novalue' ),
			SnakFormatter::FORMAT_PLAIN
		);

		return [
			'format must be a string' => [
				17,
				[],
				[],
			],
			'snak types must be strings' => [
				SnakFormatter::FORMAT_PLAIN,
				[ 17 => $formatter ],
				[ 'string' => $formatter ],
			],
			'data types must be strings' => [
				SnakFormatter::FORMAT_PLAIN,
				[],
				[ 17 => $formatter ],
			],
			'snak type formatters must be SnakFormatters' => [
				SnakFormatter::FORMAT_PLAIN,
				[ 'novalue' => 17 ],
				[ 'string' => $formatter ],
			],
			'data type formatters must be SnakFormatters' => [
				SnakFormatter::FORMAT_PLAIN,
				[],
				[ 'string' => 17 ],
			],
			'snak type formatters mismatches output format' => [
				SnakFormatter::FORMAT_HTML,
				[ 'novalue' => $formatter ],
				[ 'string' => $formatter ],
			],
			'data type formatters mismatches output format' => [
				SnakFormatter::FORMAT_HTML,
				[],
				[ 'string' => $formatter ],
			],
		];
	}

	public function provideFormatSnak() {
		$p23 = new PropertyId( 'P23' );

		return [
			'novalue' => [
				'NO VALUE',
				new PropertyNoValueSnak( $p23 ),
				'string'
			],
			'somevalue' => [
				'SOME VALUE',
				new PropertySomeValueSnak( $p23 ),
				'string'
			],
			'string value' => [
				'STRING VALUE',
				new PropertyValueSnak( $p23, new StringValue( 'dummy' ) ),
				'string'
			],
			'other value' => [
				'OTHER VALUE',
				new PropertyValueSnak( $p23, new StringValue( 'dummy' ) ),
				'url'
			],
		];
	}

	/**
	 * @dataProvider provideFormatSnak
	 */
	public function testFormatSnak( $expected, Snak $snak, $dataType ) {
		$formattersBySnakType = [
			'novalue' => $this->makeSnakFormatter( 'NO VALUE' ),
			'somevalue' => $this->makeSnakFormatter( 'SOME VALUE' ),
		];

		$formattersByDataType = [
			'PT:string' => $this->makeSnakFormatter( 'STRING VALUE' ),
			'*' => $this->makeSnakFormatter( 'OTHER VALUE' ),
		];

		$formatter = new DispatchingSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$this->getDataTypeLookup( $dataType ),
			$formattersBySnakType,
			$formattersByDataType
		);

		$this->assertEquals( $expected, $formatter->formatSnak( $snak ) );
	}

	public function testGetFormat() {
		$formatter = new DispatchingSnakFormatter( 'test', $this->getDataTypeLookup(), [], [] );
		$this->assertEquals( 'test', $formatter->getFormat() );
	}

}
