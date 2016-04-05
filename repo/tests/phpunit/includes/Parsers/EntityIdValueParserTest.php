<?php

namespace Wikibase\Repo\Tests\Parsers;

use ValueParsers\Test\StringValueParserTest;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Repo\Parsers\EntityIdValueParser;

/**
 * @covers Wikibase\Repo\Parsers\EntityIdValueParser
 * @uses Wikibase\DataModel\Entity\BasicEntityIdParser
 *
 * @group ValueParsers
 * @group WikibaseRepo
 * @group Wikibase
 * @group EntityIdValueParserTest
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdValueParserTest extends StringValueParserTest {

	/**
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return EntityIdValueParser
	 */
	protected function getInstance() {
		return new EntityIdValueParser( new BasicEntityIdParser() );
	}

	/**
	 * @see ValueParserTestBase::parseProvider
	 */
	public function validInputProvider() {
		$argLists = [];

		$valid = array(
			'q1' => new EntityIdValue( new ItemId( 'q1' ) ),
			'p1' => new EntityIdValue( new PropertyId( 'p1' ) ),
		);

		foreach ( $valid as $value => $expected ) {
			$argLists[] = array( $value, $expected );
		}

		return array_merge( $argLists );
	}

	/**
	 * @see StringValueParserTest::invalidInputProvider
	 */
	public function invalidInputProvider() {
		$argLists = parent::invalidInputProvider();

		$invalid = array(
			'foo',
			'c2',
			'a-1',
			'1a',
			'a1a',
			'01a',
			'a 1',
			'a1 ',
			' a1',
		);

		foreach ( $invalid as $value ) {
			$argLists[] = array( $value );
		}

		return $argLists;
	}

}
