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

	public static function constructorErrorsProvider() {
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
	public function testFormat( $value, callable $formattersFactory, string $expected ) {
		$formatters = $formattersFactory( $this );
		$formatter = new DispatchingValueFormatter(
			$formatters
		);

		$this->assertEquals( $expected, $formatter->format( $value ) );
	}

	public static function formatProvider() {
		$formattersFactory = function ( self $self ) {
			$stringFormatter = $self->createMock( ValueFormatter::class );
			$stringFormatter->method( 'format' )
				->willReturn( 'VT:string' );

			$entityIdFormatter = $self->createMock( ValueFormatter::class );
			$entityIdFormatter->method( 'format' )
				->willReturn( 'VT:wikibase-entityid' );

			return [
				'VT:string' => $stringFormatter,
				'VT:wikibase-entityid' => $entityIdFormatter,
			];
		};

		$lazyFormattersFactory = fn ( self $self ) => array_map(
			fn( $formatter ) => fn() => $formatter,
			$formattersFactory( $self ),
		);

		return [
			'match PT' => [
				new EntityIdValue( new ItemId( 'Q13' ) ),
				$formattersFactory,
				'VT:wikibase-entityid',
			],

			'match VT' => [
				new StringValue( 'something' ),
				$formattersFactory,
				'VT:string',
			],

			'match PT (lazy)' => [
				new EntityIdValue( new ItemId( 'Q13' ) ),
				$lazyFormattersFactory,
				'VT:wikibase-entityid',
			],

			'match VT (lazy)' => [
				new StringValue( 'something' ),
				$lazyFormattersFactory,
				'VT:string',
			],
		];
	}

	/**
	 * @dataProvider formatValueProvider
	 */
	public function testFormatValue( $value, string $type, callable $formattersFactory, string $expected ) {
		$formatter = new DispatchingValueFormatter(
			$formattersFactory( $this )
		);

		$this->assertEquals( $expected, $formatter->formatValue( $value, $type ) );
	}

	public static function formatValueProvider() {
		$formattersFactory = function ( self $self ) {
			$stringFormatter = $self->createMock( ValueFormatter::class );
			$stringFormatter->method( 'format' )
				->willReturn( 'VT:string' );

			$mediaFormatter = $self->createMock( ValueFormatter::class );
			$mediaFormatter->method( 'format' )
				->willReturn( 'PT:commonsMedia' );

			return [
				'VT:string' => $stringFormatter,
				'PT:commonsMedia' => $mediaFormatter,
			];
		};

		$lazyFormattersFactory = fn ( self $self ) => array_map(
			fn( $formatter ) => fn() => $formatter,
			$formattersFactory( $self ),
		);

		return [
			'match PT' => [
				new StringValue( 'Foo.jpg' ),
				'commonsMedia',
				$formattersFactory,
				'PT:commonsMedia',
			],

			'match VT' => [
				new StringValue( 'something' ),
				'someStuff',
				$formattersFactory,
				'VT:string',
			],

			'match PT (lazy)' => [
				new StringValue( 'Foo.jpg' ),
				'commonsMedia',
				$lazyFormattersFactory,
				'PT:commonsMedia',
			],

			'match VT (lazy)' => [
				new StringValue( 'something' ),
				'someStuff',
				$lazyFormattersFactory,
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

	public static function formatValueErrorsProvider(): iterable {
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
