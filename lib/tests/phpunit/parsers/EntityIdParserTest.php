<?php

namespace Wikibase\Lib\Test;

use ValueParsers\ParserOptions;
use ValueParsers\Test\StringValueParserTest;
use ValueParsers\ValueParser;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdValueParser;

/**
 * @covers Wikibase\Lib\EntityIdValueParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group EntityIdValueParserTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdValueParserTest extends StringValueParserTest {

	/**
	 * @return ValueParser
	 */
	protected function getInstance() {
		$class = $this->getParserClass();
		return new $class( new BasicEntityIdParser(), $this->newParserOptions() );
	}

	/**
	 * @see ValueParserTestBase::newParserOptions
	 *
	 * @return ParserOptions
	 */
	protected function newParserOptions() {
		return new ParserOptions();
	}

	/**
	 * @see ValueParserTestBase::parseProvider
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = array();

		$parser = new EntityIdValueParser( new BasicEntityIdParser(), $this->newParserOptions() );

		$valid = array(
			'q1' => new EntityIdValue( new ItemId( 'q1' ) ),
			'p1' => new EntityIdValue( new PropertyId( 'p1' ) ),
		);

		foreach ( $valid as $value => $expected ) {
			$argLists[] = array( $value, $expected, $parser );
		}

		return array_merge( $argLists );
	}

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

	/**
	 * @see ValueParserTestBase::getParserClass
	 *
	 * @return string
	 */
	protected function getParserClass() {
		return 'Wikibase\Lib\EntityIdValueParser';
	}

}
