<?php

namespace Wikibase\Test;

use Wikibase\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ClaimsSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\Claim;
use Wikibase\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;

/**
 * @covers Wikibase\Lib\Serializers\ClaimsSerializer
 *
 * @since 0.3
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
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
	 * @return ClaimsSerializer
	 */
	protected function getInstance() {
		$class = $this->getClass();
		return new $class( new ClaimSerializer( new SnakSerializer() ) );
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
			new Statement( new PropertySomeValueSnak( $propertyId ) ),
			new Statement( new PropertyNoValueSnak( $propertyId ) ),
			new Claim( new PropertySomeValueSnak( new PropertyId( 'P1' ) ) ),
		);

		$claims[1]->setRank( Claim::RANK_PREFERRED );

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

	public function testSortByRank() {
		$claims = array(
			new Statement( new PropertySomeValueSnak( new PropertyId( 'P2' ) ) ),
			new Statement( new PropertyNoValueSnak( new PropertyId( 'P2' ) ) ),
			new Claim( new PropertySomeValueSnak( new PropertyId( 'P1' ) ) ),
		);

		$claims[1]->setRank( Claim::RANK_PREFERRED );

		foreach ( $claims as $i => $claim ) {
			$claim->setGuid( 'ClaimsSerializerTest$claim-' . $i );
		}

		$opts = new SerializationOptions();
		$opts->setOption( SerializationOptions::OPT_SORT_BY_RANK, true );

		$serializer = new ClaimsSerializer( new ClaimSerializer( new SnakSerializer( null, $opts ), $opts ), $opts );
		$serialized = $serializer->getSerialized( new Claims( $claims ) );

		$this->assertEquals( array( 'P2', 'P1' ), array_keys( $serialized ) );
		$this->assertArrayHasKey( '0', $serialized['P2'] );
		$this->assertArrayHasKey( '1', $serialized['P2'] );
		$this->assertArrayHasKey( '0', $serialized['P1'] );

		// make sure they got sorted
		$this->assertEquals( $serialized['P2'][0]['id'], 'ClaimsSerializerTest$claim-1' );
		$this->assertEquals( $serialized['P2'][1]['id'], 'ClaimsSerializerTest$claim-0' );
	}

}
