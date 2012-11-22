<?php

namespace DataValues\Test;
use DataValues\DataValueFactory;

/**
 * Tests for the DataValues\DataValueFactory class.
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
 * @since 0.1
 *
 * @ingroup DataValueTest
 *
 * @group DataValue
 * @group DataValueExtensions
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DataValuesFactoryTest extends \MediaWikiTestCase {

	public function testSingleton() {
		$instance = DataValueFactory::singleton();

		$this->assertInstanceOf( 'DataValues\DataValueFactory', $instance );
		$this->assertTrue( DataValueFactory::singleton() === $instance );

		global $wgDataValues;

		foreach ( $wgDataValues as $dataValueType => $dataValueClass ) {
			$this->assertTrue( $instance->hasDataValue( $dataValueType ) );
		}
	}

	/**
	 * @return DataValueFactory
	 */
	protected function getNewInstance() {;
		return new DataValueFactory();
	}

	public function testRegisterDataValue() {
		$factory = $this->getNewInstance();

		$factory->registerDataValue( 'string', 'DataValues\StringValue' );
		$factory->registerDataValue( 'number', 'DataValues\NumberValue' );

		$this->assertArrayEquals( array( 'string', 'number' ), $factory->getDataValues() );

		$factory->registerDataValue( 'number', 'DataValues\StringValue' );

		$this->assertInstanceOf( 'DataValues\StringValue', $factory->newDataValue( 'number', '42' ) );
	}

	public function testNewDataValue() {
		$factory = $this->getNewInstance();

		$values = array(
			'string' => 'foo bar baz!',
			'number' => 42.0,
		);

		$dataValues = array(
			'string' => 'DataValues\StringValue',
			'number' => 'DataValues\NumberValue',
		);

		foreach ( $dataValues as $dataValueType => $dataValueClass ) {
			$factory->registerDataValue( $dataValueType, $dataValueClass );
		}

		foreach ( $factory->getDataValues() as $dataValueType ) {
			$dataValue = $factory->newDataValue( $dataValueType, $values[$dataValueType] );
			$this->assertInstanceOf( $dataValues[$dataValueType], $dataValue );
		}

		// In case there are no DataValues PHPUnit would otherwise complain here.
		$this->assertTrue( true );
	}

	// newFromArray is tested in DataValueTest

}