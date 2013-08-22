<?php

namespace Wikibase\Lib\Test;

use ValueParsers\ParserOptions;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\EntityIdParser;

/**
 * @covers Wikibase\Lib\EntityIdParser
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLibTest
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group EntityIdParserTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdParserTest extends \ValueParsers\Test\StringValueParserTest {

	/**
	 * @see \ValueParsers\Test\ValueParserTestBase::newParserOptions
	 *
	 * @since 0.4
	 *
	 * @return ParserOptions
	 */
	protected function newParserOptions() {
		return new ParserOptions();
	}

	/**
	 * @see ValueParserTestBase::parseProvider
	 *
	 * @since 0.4
	 *
	 * @return array
	 */
	public function validInputProvider() {
		$argLists = array();

		$parser = new EntityIdParser( $this->newParserOptions() );

		$valid = array(
			'q1' => new ItemId( 'q1' ),
			'p1' => new PropertyId( 'p1' ),
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
	 * @since 0.4
	 *
	 * @return string
	 */
	protected function getParserClass() {
		return 'Wikibase\Lib\EntityIdParser';
	}

}
