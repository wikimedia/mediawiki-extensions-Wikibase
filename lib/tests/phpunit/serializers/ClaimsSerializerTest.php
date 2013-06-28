<?php

namespace Wikibase\Test;
use Wikibase\EntityId;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\Claim;
use Wikibase\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * Tests for the Wikibase\ClaimsSerializer class.
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
class ClaimsSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ClaimsSerializer';
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @since 0.2
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$propertyId = new EntityId( \Wikibase\Property::ENTITY_TYPE, 42 );

		$claims = array(
			new Claim( new PropertyNoValueSnak( $propertyId ) ),
			new Claim( new PropertySomeValueSnak( new EntityId( \Wikibase\Property::ENTITY_TYPE, 1 ) ) ),
			new Statement( new PropertyNoValueSnak( $propertyId ) ),
		);

		$claimSerializer = new ClaimSerializer();

		$validArgs[] = array(
			new \Wikibase\Claims( $claims ),
			array(
				'p42' => array(
					$claimSerializer->getSerialized( $claims[0] ),
					$claimSerializer->getSerialized( $claims[2] ),
				),
				'p1' => array(
					$claimSerializer->getSerialized( $claims[1] ),
				),
			),
		);

		return $validArgs;
	}

}
