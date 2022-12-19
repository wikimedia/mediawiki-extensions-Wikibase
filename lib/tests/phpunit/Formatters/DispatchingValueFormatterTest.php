<?php

namespace Wikibase\Lib\Tests\Formatters;

use DataValues\StringValue;
use InvalidArgumentException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\FormattingException;
use ValueFormatters\StringFormatter;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Formatters\DispatchingValueFormatter;

/**
 * @covers \Wikibase\Lib\Formatters\DispatchingValueFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class DispatchingValueFormatterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider constructorErrorsProvider
	 */
	public function testConstructorErrors( array $formatters, $error ) {
		$this->expectException( $error );

		new DispatchingValueFormatter( $formatters );
	}

	public function constructorErrorsProvider() {
		$stringFormatter = new StringFormatter( new FormatterOptions() );

		return [
			'keys must be strings' => [
				[ 17 => $stringFormatter ],
				InvalidArgumentException::class,
			],
			'keys must have prefix' => [
				[ 'foo' => $stringFormatter ],
				InvalidArgumentException::class,
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
		$stringFormatter = $this->createMock( ValueFormatter::class );
		$stringFormatter->method( 'format' )
			->willReturn( 'VT:string' );

		$mediaFormatter = $this->createMock( ValueFormatter::class );
		$mediaFormatter->method( 'format' )
			->willReturn( 'VT:wikibase-entityid' );

		$formatters = [
			'VT:string' => $stringFormatter,
			'VT:wikibase-entityid' => $mediaFormatter,
		];
		$lazyFormatters = array_map( fn( $formatter ) => fn() => $formatter, $formatters );

		return [
			'match PT' => [
				new EntityIdValue( new ItemId( 'Q13' ) ),
				$formatters,
				'VT:wikibase-entityid',
			],

			'match VT' => [
				new StringValue( 'something' ),
				$formatters,
				'VT:string',
			],

			'match PT (lazy)' => [
				new EntityIdValue( new ItemId( 'Q13' ) ),
				$lazyFormatters,
				'VT:wikibase-entityid',
			],

			'match VT (lazy)' => [
				new StringValue( 'something' ),
				$lazyFormatters,
				'VT:string',
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
		$stringFormatter = $this->createMock( ValueFormatter::class );
		$stringFormatter->method( 'format' )
			->willReturn( 'VT:string' );

		$mediaFormatter = $this->createMock( ValueFormatter::class );
		$mediaFormatter->method( 'format' )
			->willReturn( 'PT:commonsMedia' );

		$formatters = [
			'VT:string' => $stringFormatter,
			'PT:commonsMedia' => $mediaFormatter,
		];
		$lazyFormatters = array_map( fn( $formatter ) => fn() => $formatter, $formatters );

		return [
			'match PT' => [
				new StringValue( 'Foo.jpg' ),
				'commonsMedia',
				$formatters,
				'PT:commonsMedia',
			],

			'match VT' => [
				new StringValue( 'something' ),
				'someStuff',
				$formatters,
				'VT:string',
			],

			'match PT (lazy)' => [
				new StringValue( 'Foo.jpg' ),
				'commonsMedia',
				$lazyFormatters,
				'PT:commonsMedia',
			],

			'match VT (lazy)' => [
				new StringValue( 'something' ),
				'someStuff',
				$lazyFormatters,
				'VT:string',
			],
		];
	}

	/** @dataProvider formatValueErrorsProvider */
	public function testFormatValueErrors( $value, $type, $formatters ): void {
		$formatter = new DispatchingValueFormatter( $formatters );

		$this->expectException( FormattingException::class );
		$formatter->formatValue( $value, $type );
	}

	public function formatValueErrorsProvider(): iterable {
		yield 'invalid type PT' => [
			new StringValue( 'Foo.jpg' ),
			'commonsMedia',
			[ 'PT:commonsMedia' => 17 ],
		];
		yield 'invalid type VT' => [
			new StringValue( 'Foo.jpg' ),
			'commonsMedia',
			[ 'VT:string' => 17 ],
		];

		yield 'invalid type PT (lazy)' => [
			new StringValue( 'Foo.jpg' ),
			'commonsMedia',
			[ 'PT:commonsMedia' => fn() => 17 ],
		];
		yield 'invalid type VT (lazy)' => [
			new StringValue( 'Foo.jpg' ),
			'commonsMedia',
			[ 'VT:string' => fn() => 17 ],
		];
	}

}
