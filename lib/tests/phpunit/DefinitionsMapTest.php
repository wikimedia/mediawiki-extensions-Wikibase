<?php

namespace Wikibase\Lib\Tests;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\Lib\DefinitionsMap;

/**
 * @covers Wikibase\Lib\DefinitionsMap
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class DefinitionsMapTest extends PHPUnit_Framework_TestCase {

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testConstruct_invalidKey() {
		new DefinitionsMap( array( array() ) );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testConstruct_invalidValue() {
		new DefinitionsMap( array( 'foo' => 'bar' ) );
	}

	public function provideDefinitions() {
		$definitions = array(
			'foo' => array(
				'abc' => 'foo-123',
				'def' => 'foo-456'
			),
			'bar' => array(
				'abc' => 'bar-135'
			)
		);

		return array(
			array(
				$definitions,
				array(
					'foo' => 'foo-123',
					'bar' => 'bar-135'
				),
				'abc'
			),
			array(
				$definitions,
				array(
					'foo' => 'foo-456'
				),
				'def'
			)
		);
	}

	/**
	 * @dataProvider provideDefinitions
	 */
	public function testGetMapForDefinitionField( array $definitions, array $expected, $key ) {
		$map = new DefinitionsMap( $definitions );

		$this->assertEquals( $expected, $map->getMapForDefinitionField( $key ) );
	}

	public function testGetKeys() {
		$map = new DefinitionsMap( array(
			'foo' => array(),
			'bar' => array()
		) );

		$this->assertEquals(
			array( 'foo', 'bar' ),
			$map->getKeys()
		);
	}

	public function testToArray() {
		$definitions = array(
			'foo' => array(),
			'bar' => array()
		);

		$map = new DefinitionsMap( $definitions );

		$this->assertSame( $definitions, $map->toArray() );
	}

}