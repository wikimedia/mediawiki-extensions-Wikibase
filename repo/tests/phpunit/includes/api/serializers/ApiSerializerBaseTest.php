<?php

namespace Wikibase\Test;
use Wikibase\ApiSerializerObject;

/**
 * Base class for tests that test classes deriving from Wikibase\ApiSerializerObject.
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
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseApiSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class ApiSerializerBaseTest extends \MediaWikiTestCase {

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
	 * @return ApiSerializerObject
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class( new \ApiResult( new \ApiMain() ) );
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @since 0.2
	 */
	public function testGetSerializedValid( $input, array $expected = null ) {
		$serializer = $this->getInstance();

		$output = $serializer->getSerialized( $input );
		$this->assertInternalType( 'array', $output );

		if ( $expected !== null ) {
			$this->assertEquals( $expected, $output );
		}
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
		$this->assertException( function() use ( $serializer, $input ) { $serializer->getSerialized( $input ); } );
	}

	/**
	 * Asserts that an exception of the specified type occurs when running
	 * the provided code.
	 *
	 * @since 0.2
	 *
	 * @param string $expected
	 * @param callable $code
	 */
	protected function assertException( $code, $expected = 'Exception' ) {
		$pokemons = null;

		try {
			call_user_func( $code );
		}
		catch ( \Exception $pokemons ) {
			// Gotta Catch 'Em All!
		}

		$this->assertInstanceOf( $expected, $pokemons );
	}

}
