<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Serializers\ByPropertyListSerializer;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SnakSerializer;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\SnakList;
use Wikibase\Statement;

/**
 * @covers Wikibase\Lib\Serializers\ByPropertyListSerializer
 *
 * @since 0.2
 *
 * @group WikibaseLib
 * @group Wikibase
 * @group WikibaseSerialization
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ByPropertyListSerializerTest extends SerializerBaseTest {

	/**
	 * @see SerializerBaseTest::getClass
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	protected function getClass() {
		return '\Wikibase\Lib\Serializers\ByPropertyListSerializer';
	}

	/**
	 * @since 0.2
	 *
	 * @return ByPropertyListSerializer
	 */
	protected function getInstance() {
		$snakSerializer = new SnakSerializer();
		return new ByPropertyListSerializer( 'test', $snakSerializer );
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

		$dataValue0 = new StringValue( 'ohi' );

		$id42 = new PropertyId( 'p42' );
		$id2 = new PropertyId( 'p2' );

		$snak0 = new PropertyNoValueSnak( $id42 );
		$snak1 = new PropertySomeValueSnak( $id2 );
		$snak2 = new PropertyValueSnak( $id2, $dataValue0 );

		$validArgs[] = new SnakList( array( $snak0, $snak1, $snak2 ) );

		$validArgs = $this->arrayWrap( $validArgs );

		$validArgs[ 'Empty' ] = array(
			new SnakList(),
			array(),
		);

		$validArgs[ 'Default' ] = array(
			new SnakList( array( $snak0, $snak1, $snak2 ) ),
			array(
				'P42' => array(
					0 => array(
						'snaktype' => 'novalue',
						'property' => 'P42',
					),
				),
				'P2' => array(
					0 => array(
						'snaktype' => 'somevalue',
						'property' => 'P2',
					),
					1 => array(
						'snaktype' => 'value',
						'property' => 'P2',
						'datavalue' => $dataValue0->toArray(),
					),
				),
			),
		);

		$options = new SerializationOptions();
		$options->setIdKeyMode( SerializationOptions::ID_KEYS_LOWER );

		$validArgs[ 'ID_KEYS_LOWER' ] = array(
			new SnakList( array( $snak0, $snak1, $snak2 ) ),
			array(
				'p42' => array(
					0 => array(
						'snaktype' => 'novalue',
						'property' => 'P42',
					),
				),
				'p2' => array(
					0 => array(
						'snaktype' => 'somevalue',
						'property' => 'P2',
					),
					1 => array(
						'snaktype' => 'value',
						'property' => 'P2',
						'datavalue' => $dataValue0->toArray(),
					),
				),
			),
			$options
		);

		$options = new SerializationOptions();
		$options->setIdKeyMode( SerializationOptions::ID_KEYS_UPPER );

		$validArgs[ 'ID_KEYS_UPPER' ] = array(
			new SnakList( array( $snak0, $snak1, $snak2 ) ),
			array(
				'P42' => array(
					0 => array(
						'snaktype' => 'novalue',
						'property' => 'P42',
					),
				),
				'P2' => array(
					0 => array(
						'snaktype' => 'somevalue',
						'property' => 'P2',
					),
					1 => array(
						'snaktype' => 'value',
						'property' => 'P2',
						'datavalue' => $dataValue0->toArray(),
					),
				),
			),
			$options
		);


		$options = new SerializationOptions();
		$options->setIdKeyMode( SerializationOptions::ID_KEYS_BOTH );

		$validArgs[ 'ID_KEYS_BOTH' ] = array(
			new SnakList( array( $snak0, $snak1, $snak2 ) ),
			array(
				'P42' => array(
					0 => array(
						'snaktype' => 'novalue',
						'property' => 'P42',
					),
				),
				'P2' => array(
					0 => array(
						'snaktype' => 'somevalue',
						'property' => 'P2',
					),
					1 => array(
						'snaktype' => 'value',
						'property' => 'P2',
						'datavalue' => $dataValue0->toArray(),
					),
				),
				'p42' => array(
					0 => array(
						'snaktype' => 'novalue',
						'property' => 'P42',
					),
				),
				'p2' => array(
					0 => array(
						'snaktype' => 'somevalue',
						'property' => 'P2',
					),
					1 => array(
						'snaktype' => 'value',
						'property' => 'P2',
						'datavalue' => $dataValue0->toArray(),
					),
				),
			),
			$options
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

		$serializer = new ByPropertyListSerializer( 'claim', new ClaimSerializer( new SnakSerializer( null, $opts ), $opts ), $opts );
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
