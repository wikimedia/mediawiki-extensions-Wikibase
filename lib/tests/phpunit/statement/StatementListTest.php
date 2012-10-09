<?php

namespace Wikibase\Test;
use Wikibase\StatementList;
use Wikibase\Statements;
use Wikibase\Statement;
use Wikibase\StatementObject;
use Wikibase\Hashable;
use Wikibase\ClaimObject;
use Wikibase\PropertyNoValueSnak;

/**
 * Tests for the Wikibase\StatementList class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.2
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStatement
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class StatementListTest extends \MediaWikiTestCase {

	public function getInstanceClass() {
		return '\Wikibase\StatementList';
	}

	public function instanceProvider() {
		$class = $this->getInstanceClass();

		$instances = array();

		foreach ( $this->getConstructorArg() as $arg ) {
			$instances[] = array( new $class( $arg ) );
		}

		return $instances;
	}

	public function getElementInstances() {
		$instances = array();

		$instances[] = new StatementObject( new ClaimObject( new PropertyNoValueSnak( 42 ) ) );

		$instances[] = new StatementObject(
			new ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) ),
			new \Wikibase\ReferenceList(
				new \Wikibase\ReferenceObject( new \Wikibase\SnakList( new PropertyNoValueSnak( 23 ) ) )
			)
		);

		return $instances;
	}

	public function getConstructorArg() {
		return array(
			null,
			array(),
			$this->getElementInstances(),
		);
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\StatementList $array
	 */
	public function testHasStatement( StatementList $array ) {
		/**
		 * @var Hashable $hashable
		 */
		foreach ( iterator_to_array( $array ) as $hashable ) {
			$this->assertTrue( $array->hasStatement( $hashable ) );
			$array->removeStatement( $hashable );
			$this->assertFalse( $array->hasStatement( $hashable ) );
		}

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\StatementList $array
	 */
	public function testRemoveStatement( StatementList $array ) {
		$elementCount = count( $array );

		/**
		 * @var Hashable $element
		 */
		foreach ( iterator_to_array( $array ) as $element ) {
			$this->assertTrue( $array->hasStatement( $element ) );

			$array->removeStatement( $element );

			$this->assertFalse( $array->hasStatement( $element ) );
			$this->assertEquals( --$elementCount, count( $array ) );
		}

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		$array->removeStatement( $element );
		$array->removeStatement( $element );

		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \Wikibase\StatementList $array
	 */
	public function testAddStatement( StatementList $array ) {
		$elementCount = count( $array );

		$elements = $this->getElementInstances();
		$element = array_shift( $elements );

		if ( !$array->hasStatement( $element ) ) {
			++$elementCount;
		}

		$array->addStatement( $element );

		$this->assertEquals( $elementCount, count( $array ) );
	}

}
