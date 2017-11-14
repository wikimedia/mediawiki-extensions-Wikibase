<?php

namespace Wikibase\Repo\Tests;

use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use ValueParsers\NullParser;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\ValueParserFactory;

/**
 * @covers Wikibase\Repo\ValueParserFactory
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 */
class ValueParserFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideInvalidConstructorArgument
	 */
	public function testInvalidConstructorArgument( array $valueParsers ) {
		$this->setExpectedException( InvalidArgumentException::class );
		new ValueParserFactory( $valueParsers );
	}

	public function provideInvalidConstructorArgument() {
		return [
			'value is not a callable' => [ [
				'id' => 'not a callable',
			] ],
			'id is not a string' => [ [
				function () {
				},
			] ],
		];
	}

	public function testNewParser_withUnknownParserId() {
		$factory = new ValueParserFactory( [] );

		$this->setExpectedException( OutOfBoundsException::class );
		$factory->newParser( 'unknown', new ParserOptions() );
	}

	public function testNewParser_withInvalidReturnValue() {
		$factory = new ValueParserFactory( [ 'id' => function () {
			return 'invalid';
		} ] );

		$this->setExpectedException( LogicException::class );
		$factory->newParser( 'id', new ParserOptions() );
	}

	/**
	 * @dataProvider provideFactoryFunctions
	 */
	public function testGetParserIds( $factoryFunctions ) {
		$valueParserFactory = new ValueParserFactory( $factoryFunctions );

		$returnValue = $valueParserFactory->getParserIds();

		$this->assertEquals( array_keys( $factoryFunctions ), $returnValue );
	}

	public function provideFactoryFunctions() {
		return [
			[
				[
					'foo' => function() {
						return new NullParser();
					}
				],
			]
		];
	}

	/**
	 * @dataProvider provideFactoryFunctions
	 */
	public function testNewParser( $factoryFunctions ) {
		$valueParserFactory = new ValueParserFactory( $factoryFunctions );
		$options = new ParserOptions();

		foreach ( $valueParserFactory->getParserIds() as $id ) {
			$parser = $valueParserFactory->newParser( $id, $options );
			$this->assertInstanceOf( ValueParser::class, $parser );
		}
	}

}
