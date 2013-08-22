<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\Claim;
use Wikibase\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ClaimsSerializer
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

		$propertyId = new PropertyId( 'P42' );

		$claims = array(
			new Claim( new PropertyNoValueSnak( $propertyId ) ),
			new Claim( new PropertySomeValueSnak( new PropertyId( 'P1' ) ) ),
			new Statement( new PropertyNoValueSnak( $propertyId ) ),
		);

		$claimSerializer = new ClaimSerializer();

		$validArgs[] = array(
			new \Wikibase\Claims( $claims ),
			array(
				'P42' => array(
					$claimSerializer->getSerialized( $claims[0] ),
					$claimSerializer->getSerialized( $claims[2] ),
				),
				'P1' => array(
					$claimSerializer->getSerialized( $claims[1] ),
				),
			),
		);

		return $validArgs;
	}

}
