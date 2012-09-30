<?php

namespace DataValues\Test;
use DataValues\MultilingualTextValue;
use DataValues\MonolingualTextValue;

/**
 * Tests for the DataValues\MultilingualTextValue class.
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
 * @since 0.1
 *
 * @ingroup DataValue
 *
 * @group DataValue
 * @group DataValueExtensions
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MultilingualTextTest extends DataValueTest {

	/**
	 * @see DataValueTest::getClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getClass() {
		return 'DataValues\MultilingualTextValue';
	}

	/**
	 * @see DataValueTest::constructorProvider
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public function constructorProvider() {
		$argLists = array();

		$argLists[] = array( false );
		$argLists[] = array( false, 42 );
		$argLists[] = array( false, false );
		$argLists[] = array( false, true );
		$argLists[] = array( false, null );
		$argLists[] = array( false, 'foo' );
		$argLists[] = array( false, 'en' );
		$argLists[] = array( false, 'en', 42 );
		$argLists[] = array( false, 'en', false );
		$argLists[] = array( false, 'en', array() );
		$argLists[] = array( false, 'en', null );
		$argLists[] = array( false, '', 'foo' );
		$argLists[] = array( false, 'en', 'foo' );
		$argLists[] = array( false, 'en', ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' );

		$argLists[] = array( true, array() );
		$argLists[] = array( true, array( 'en' => 'foo' ) );
		$argLists[] = array( true, array( 'en' => 'foo', 'de' => 'foo' ) );
		$argLists[] = array( true, array( 'en' => 'foo', 'de' => 'bar' ) );
		$argLists[] = array( true, array( 'en' => 'foo', 'de' => ' foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz foo bar baz ' ) );

		$argLists[] = array( false, array( 'foo' ) );
		$argLists[] = array( false, array( 42 => 'foo' ) );
		$argLists[] = array( false, array( '' => 'foo' ) );
		$argLists[] = array( false, array( 'en' => 42 ) );
		$argLists[] = array( false, array( 'en' => null ) );
		$argLists[] = array( false, array( 'en' => true ) );
		$argLists[] = array( false, array( 'en' => array() ) );
		$argLists[] = array( false, array( 'en' => 4.2 ) );

		return $argLists;
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\MultilingualTextValue $texts
	 * @param array $arguments
	 */
	public function testGetTexts( MultilingualTextValue $texts, array $arguments ) {
		$actual = $texts->getTexts();
		$this->assertInternalType( 'array', $actual );
		$this->assertArrayEquals( $arguments[0], $actual, false, true );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param \DataValues\MultilingualTextValue $texts
	 * @param array $arguments
	 */
	public function testGetMonolingualTextValues( MultilingualTextValue $texts, array $arguments ) {
		$actual = $texts->getMonolingualTextValues();
		$monolingualValues = array();

		$this->assertInternalType( 'array', $actual );

		/**
		 * @var MonolingualTextValue $monolingualValue
		 */
		foreach ( $actual as $monolingualValue ) {
			$this->assertInstanceOf( '\DataValues\MonolingualTextValue', $monolingualValue );
			$monolingualValues[$monolingualValue->getLanguageCode()] = $monolingualValue->getValue();
		}

		$this->assertArrayEquals( $arguments[0], $monolingualValues, false, true );
	}

}
