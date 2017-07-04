<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Formatters\DispatchingValueFormatter;

/**
 * @covers Wikibase\Lib\Formatters\DispatchingValueFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class DispatchingValueFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( array $formatters, $error ) {
		$this->setExpectedException( $error );

		new DispatchingValueFormatter( $formatters );
	}

	public function constructorErrorsProvider() {
		$stringFormatter = new StringFormatter( new FormatterOptions() );

		return [
			'keys must be strings' => [
				[ 17 => $stringFormatter ],
				InvalidArgumentException::class
			],
			'keys must have prefix' => [
				[ 'foo' => $stringFormatter ],
				InvalidArgumentException::class
			],
			'formatters must be instances of ValueFormatter' => [
				[ 'novalue' => 17 ],
				InvalidArgumentException::class
			],
		];
	}

	/**
	 * @dataProvider formatProvider
	 */
	public function testFormat( $value, $formatters, $expected ) {
		$formatter = new DispatchingValueFormatter(
			$formatters
		);

		$this->assertEquals( $expected, $formatter->format( $value ) );
	}

	public function formatProvider() {
		$stringFormatter = $this->getMock( ValueFormatter::class );
		$stringFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'VT:string' ) );

		$mediaFormatter = $this->getMock( ValueFormatter::class );
		$mediaFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'VT:wikibase-entityid' ) );

		$formatters = [
			'VT:string' => $stringFormatter,
			'VT:wikibase-entityid' => $mediaFormatter,
		];

		return [
			'match PT' => [
				new EntityIdValue( new ItemId( 'Q13' ) ),
				$formatters,
				'VT:wikibase-entityid'
			],

			'match VT' => [
				new StringValue( 'something' ),
				$formatters,
				'VT:string'
			],
		];
	}

	/**
	 * @dataProvider formatValueProvider
	 */
	public function testFormatValue( $value, $type, $formatters, $expected ) {
		$formatter = new DispatchingValueFormatter(
			$formatters
		);

		$this->assertEquals( $expected, $formatter->formatValue( $value, $type ) );
	}

	public function formatValueProvider() {
		$stringFormatter = $this->getMock( ValueFormatter::class );
		$stringFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'VT:string' ) );

		$mediaFormatter = $this->getMock( ValueFormatter::class );
		$mediaFormatter->expects( $this->any() )
			->method( 'format' )
			->will( $this->returnValue( 'PT:commonsMedia' ) );

		$formatters = [
			'VT:string' => $stringFormatter,
			'PT:commonsMedia' => $mediaFormatter,
		];

		return [
			'match PT' => [
				new StringValue( 'Foo.jpg' ),
				'commonsMedia',
				$formatters,
				'PT:commonsMedia'
			],

			'match VT' => [
				new StringValue( 'something' ),
				'someStuff',
				$formatters,
				'VT:string'
			],
		];
	}

}
