<?php

namespace Wikibase\Tests\Repo;

use ValueParsers\NullParser;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\ValueParserFactory;

/**
 * @covers Wikibase\Repo\ValueParserFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRepoTest
 *
 * @license GPL-2.0+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
class ValueParserFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideFactoryFunctions
	 */
	public function testGetParserIds( $factoryFunctions ) {
		$valueParserFactory = new ValueParserFactory( $factoryFunctions );

		$returnValue = $valueParserFactory->getParserIds();

		$this->assertEquals( array_keys( $factoryFunctions ), $returnValue );
	}

	public function provideFactoryFunctions() {
		return array(
			array(
				array(
					'foo' => function() {
						return new NullParser();
					}
				),
			)
		);
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
