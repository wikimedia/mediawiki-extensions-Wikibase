<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\DataValue;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Parsers\EntityIdValueParser;

/**
 * @covers \Wikibase\Repo\Parsers\EntityIdValueParser
 * @uses Wikibase\DataModel\Entity\BasicEntityIdParser
 *
 * @group ValueParsers
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdValueParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return EntityIdValueParser
	 */
	protected function getInstance() {
		return new EntityIdValueParser( new BasicEntityIdParser() );
	}

	/**
	 * @inheritDoc
	 */
	public function validInputProvider() {
		$valid = [
			'q1' => new EntityIdValue( new ItemId( 'q1' ) ),
			'p1' => new EntityIdValue( new NumericPropertyId( 'p1' ) ),
		];

		foreach ( $valid as $value => $expected ) {
			yield [ $value, $expected ];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function invalidInputProvider() {
		$invalid = [
			'foo',
			'c2',
			'a-1',
			'1a',
			'a1a',
			'01a',
			'a 1',
			'a1 ',
			' a1',
		];

		foreach ( $invalid as $value ) {
			yield [ $value ];
		}
	}

	public function testSetAndGetOptions() {
		$parser = $this->getInstance();

		$parser->setOptions( new ParserOptions() );

		$this->assertEquals( new ParserOptions(), $parser->getOptions() );

		$options = new ParserOptions();
		$options->setOption( 'someoption', 'someoption' );

		$parser->setOptions( $options );

		$this->assertEquals( $options, $parser->getOptions() );
	}

	/**
	 * @dataProvider validInputProvider
	 * @param mixed $value
	 * @param mixed $expected
	 * @param ValueParser|null $parser
	 */
	public function testParseWithValidInputs( $value, $expected, ValueParser $parser = null ) {
		if ( $parser === null ) {
			$parser = $this->getInstance();
		}

		$this->assertSmartEquals( $expected, $parser->parse( $value ) );
	}

	/**
	 * @param DataValue|mixed $expected
	 * @param DataValue|mixed $actual
	 */
	private function assertSmartEquals( $expected, $actual ) {
		if ( $this->requireDataValue() ) {
			if ( $expected instanceof DataValue && $actual instanceof DataValue ) {
				$msg = "testing equals():\n"
					. preg_replace( '/\s+/', ' ', print_r( $actual->toArray(), true ) ) . " should equal\n"
					. preg_replace( '/\s+/', ' ', print_r( $expected->toArray(), true ) );
			} else {
				$msg = 'testing equals()';
			}

			$this->assertTrue( $expected->equals( $actual ), $msg );
		} else {
			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 * @dataProvider invalidInputProvider
	 * @param mixed $value
	 * @param ValueParser|null $parser
	 */
	public function testParseWithInvalidInputs( $value, ValueParser $parser = null ) {
		if ( $parser === null ) {
			$parser = $this->getInstance();
		}

		$this->expectException( 'ValueParsers\ParseException' );
		$parser->parse( $value );
	}

	/**
	 * Returns if the result of the parsing process should be checked to be a DataValue.
	 *
	 * @return bool
	 */
	protected function requireDataValue() {
		return true;
	}

}
