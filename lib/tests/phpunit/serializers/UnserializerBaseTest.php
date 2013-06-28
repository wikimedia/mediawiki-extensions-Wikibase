<?php

namespace Wikibase\Lib\Test\Serializers;
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
 * @since 0.4
 *
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class UnserializerBaseTest extends \MediaWikiTestCase {

	/**
	 * @since 0.4
	 *
	 * @return string
	 */
	protected abstract function getClass();

	/**
	 * @since 0.4
	 *
	 * @return array
	 */
	public abstract function validProvider();

	/**
	 * @since 0.4
	 *
	 * @return Unserializer
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class();
	}



	/**
	 * @since 0.4
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
		);

		return $this->arrayWrap( $this->arrayWrap( $invalid ) );
	}

	/**
	 * @dataProvider invalidProvider
	 *
	 * @since 0.4
	 */
	public function testNewFromSerializationInvalid( $input ) {
		$serializer = $this->getInstance();
		$this->assertException( function() use ( $serializer, $input ) { $serializer->newFromSerialization( $input ); } );
	}

}
