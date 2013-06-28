<?php

namespace Wikibase\Test;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * Tests for the Wikibase\ReferenceSerializer class.
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
 * @since 0.3
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ReferenceSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.3
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ReferenceSerializer';
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @since 0.3
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$snaks =  array(
			new \Wikibase\PropertyNoValueSnak( 42 ),
			new \Wikibase\PropertySomeValueSnak( 1 ),
			new \Wikibase\PropertySomeValueSnak( 42 ),
			new \Wikibase\PropertyValueSnak( 1, new \DataValues\StringValue( 'foobar' ) ),
			new \Wikibase\PropertyValueSnak( 9001, new \DataValues\StringValue( 'foobar' ) ),
		);

		$snakSerializer = new SnakSerializer();

		$reference = new \Wikibase\Reference( new \Wikibase\SnakList( $snaks ) );

		$validArgs[] = array(
			$reference,
			array(
				'hash' => $reference->getHash(),
				'snaks' => array(
					'p42' => array(
						$snakSerializer->getSerialized( $snaks[0] ),
						$snakSerializer->getSerialized( $snaks[2] ),
					),
					'p1' => array(
						$snakSerializer->getSerialized( $snaks[1] ),
						$snakSerializer->getSerialized( $snaks[3] ),
					),
					'p9001' => array(
						$snakSerializer->getSerialized( $snaks[4] ),
					),
				),
			),
		);

		return $validArgs;
	}

}
