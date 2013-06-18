<?php

namespace Wikibase\Test;
use Wikibase\Lib\Serializers\SerializerObject;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\Unserializer;

/**
 * Base class for tests that test classes deriving from Wikibase\SerializerObject.
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
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class SerializerBaseTest extends \MediaWikiTestCase {

	/**
	 * @since 0.2
	 *
	 * @return string
	 */
	protected abstract function getClass();

	/**
	 * @since 0.2
	 *
	 * @return array
	 */
	public abstract function validProvider();

	/**
	 * @since 0.2
	 *
	 * @return SerializerObject
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class();
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @since 0.2
	 */
	public function testGetSerializedValid( $input, array $expected = null, SerializationOptions $options = null ) {
		$serializer = $this->getInstance();

		if ( $options !== null ) {
			$serializer->setOptions( $options );
		}

		$output = $serializer->getSerialized( $input );
		$this->assertInternalType( 'array', $output );

		if ( $expected !== null ) {
			$this->assertEquals( $expected, $output );
		}

		if ( $serializer instanceof Unserializer ) {
			$roundtrippedValue = $serializer->newFromSerialization( $output );
			$this->assertMeaningfulEquals( $input, $roundtrippedValue, 'getSerialized, getUnserialized roundtrip should result in input value' );
		}
	}

	/**
	 * Assert equality using comparison methods when available.
	 *
	 * @since 0.3
	 *
	 * @param mixed $expected
	 * @param mixed $actual
	 * @param string $message
	 */
	protected function assertMeaningfulEquals( $expected, $actual, $message = '' ) {
		if ( is_object( $expected ) ) {
			if ( $expected instanceof \Comparable ) {
				$this->assertTrue( $expected->equals( $actual ), $message );
				return;
			}

			if ( $expected instanceof \Hashable ) {
				$this->assertInstanceOf( '\Hashable', $actual, $message );
				$this->assertEquals( $expected->getHash( $actual ), $actual->getHash(), $message );
				return;
			}
		}

		$this->assertEquals( $expected, $actual, $message );
	}

	/**
	 * @since 0.2
	 *
	 * @return array
	 */
	public function invalidProvider() {
		$invalid = array(
			false,
			true,
			null,
			42,
			4.2,
			'',
			'foo bar baz',
			array()
		);

		return $this->arrayWrap( $invalid );
	}

	/**
	 * @dataProvider invalidProvider
	 *
	 * @since 0.2
	 */
	public function testGetSerializedInvalid( $input ) {
		$serializer = $this->getInstance();

		$this->setExpectedException( 'Exception' );
		$serializer->getSerialized( $input );
	}

}
