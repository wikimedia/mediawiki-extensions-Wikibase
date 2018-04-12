<?php

namespace Wikibase\Repo\Tests\Parsers;

use PHPUnit4And6Compat;
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
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdValueParserTest extends StringValueParserTest {
	use PHPUnit4And6Compat;

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

		$valid = [
			'q1' => new EntityIdValue( new ItemId( 'q1' ) ),
			'p1' => new EntityIdValue( new PropertyId( 'p1' ) ),
		];

		foreach ( $valid as $value => $expected ) {
			$argLists[] = [ $value, $expected ];
		}

		return array_merge( $argLists );
	}

	/**
	 * @see StringValueParserTest::invalidInputProvider
	 */
	public function invalidInputProvider() {
		$argLists = parent::invalidInputProvider();

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
			$argLists[] = [ $value ];
		}

		return $argLists;
	}

}
