<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\ClaimsSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SnakSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ClaimsSerializer
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
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ClaimsSerializer';
	}

	/**
	 * @return ClaimsSerializer
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class( new ClaimSerializer( new SnakSerializer() ) );
	}

	/**
	 * @see SerializerBaseTest::validProvider
	 *
	 * @return array
	 */
	public function validProvider() {
		$validArgs = array();

		$propertyId = new PropertyId( 'P42' );

		$claims = array(
			new Claim( new PropertyNoValueSnak( $propertyId ) ),
			new Statement( new PropertyNoValueSnak( $propertyId ) ),
			new Claim( new PropertySomeValueSnak( new PropertyId( 'P1' ) ) ),
		);

		foreach ( $claims as $i => $claim ) {
			$claim->setGuid( 'ClaimsSerializerTest$claim-' . $i );
		}

		$claimSerializer = new ClaimSerializer( new SnakSerializer() );

		$validArgs['grouped'] = array(
			new Claims( $claims ),
			array(
				'P42' => array(
					$claimSerializer->getSerialized( $claims[0] ),
					$claimSerializer->getSerialized( $claims[1] ),
				),
				'P1' => array(
					$claimSerializer->getSerialized( $claims[2] ),
				),
			),
		);

		$opts = new SerializationOptions();
		$opts->setOption( SerializationOptions::OPT_GROUP_BY_PROPERTIES, array() );

		$validArgs['list'] = array(
			new Claims( $claims ),
			array(
				$claimSerializer->getSerialized( $claims[0] ),
				$claimSerializer->getSerialized( $claims[1] ),
				$claimSerializer->getSerialized( $claims[2] ),
			),
			$opts
		);

		return $validArgs;
	}

}
