<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\Lib\ClaimGuidGenerator;
use FormatJson;
use UsageException;

/**
 * @covers Wikibase\Api\SetQualifier
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetQualifierTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class SetQualifierTest extends WikibaseApiTestCase {

	/**
	 * Creates a Snak of the given type with the given data.
	 *
	 * @param string $type
	 * @param mixed $data
	 *
	 * @return Snak
	 */
	public function getTestSnak( $type, $data = null ) {
		static $snaks = array();

		if ( !isset( $snaks[$type] ) ) {
			$prop = Property::newEmpty();
			$propertyId = $this->makeProperty( $prop, 'string' )->getId();

			$snaks[$type] = new $type( $propertyId, $data );
			$this->assertInstanceOf( 'Wikibase\Snak', $snaks[$type] );
		}

		return $snaks[$type];
	}

	/**
	 * Creates the given property in the database, if necessary.
	 *
	 * @param Property $property
	 * @param $type
	 *
	 * @return Property
	 */
	protected function makeProperty( Property $property, $type ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$property->setDataTypeId( $type );

		$store->saveEntity( $property, 'testing', $GLOBALS['wgUser'], EDIT_NEW );
		return $property;
	}


	protected function getTestItem() {
		static $item = null;

		if ( !$item ) {
			$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

			$item = Item::newEmpty();
			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

			$prop = Property::newEmpty();
			$propId = $this->makeProperty( $prop, 'string' )->getId();
			$claim = new Statement( new PropertyValueSnak( $propId, new StringValue( '^_^' ) ) );

			$guidGenerator = new ClaimGuidGenerator( $item->getId() );
			$claim->setGuid( $guidGenerator->newGuid() );
			$item->addClaim( $claim );

			$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );
		}

		return $item;
	}

	public function provideAddRequests() {
		return array(
			array( 'Wikibase\PropertyNoValueSnak' ),
			array( 'Wikibase\PropertySomeValueSnak' ),
			array( 'Wikibase\PropertyValueSnak', new StringValue( 'o_O' ) )
		);
	}

	/**
	 * @dataProvider provideAddRequests
	 */
	public function testAddRequests( $snakType, $data = null ) {
		$item = $this->getTestItem();
		$claims = $item->getClaims();
		$claim = reset( $claims );

		$snak = $this->getTestSnak( $snakType, $data );

		$this->makeSetQualifierRequest( $claim->getGuid(), null, $snak, $item->getId() );

		// now the hash exists, so the same request should fail
		$this->setExpectedException( 'UsageException' );
		$this->makeSetQualifierRequest( $claim->getGuid(), null, $snak, $item->getId() );
	}

	public function provideChangeRequests() {
		return array( array( 'Wikibase\PropertyValueSnak', new StringValue( 'o_O' ) ) );
	}

	/**
	 * @dataProvider provideChangeRequests
	 */
	public function testChangeRequests( $snakType, $data = null ) {
		$item = $this->getTestItem();
		$claims = $item->getClaims();
		$claim = reset( $claims );

		$snak = $this->getTestSnak( $snakType, $data );

		static $counter = 1;
		$hash = $snak->getHash();
		$newQualifier = new PropertyValueSnak( $snak->getPropertyId(), new StringValue( __METHOD__ . '#' . $counter++ ) );

		$this->makeSetQualifierRequest( $claim->getGuid(), $hash, $newQualifier, $item->getId() );

		// now the hash changed, so the same request should fail
		$this->setExpectedException( 'UsageException' );
		$this->makeSetQualifierRequest( $claim->getGuid(), $hash, $newQualifier, $item->getId() );
	}

	protected function makeSetQualifierRequest( $statementGuid, $snakhash, Snak $qualifier, EntityId $entityId ) {
		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $statementGuid,
			'snakhash' => $snakhash,
			'snaktype' => $qualifier->getType(),
			'property' => $qualifier->getPropertyId()->getSerialization(),
		);

		if ( $qualifier instanceof PropertyValueSnak ) {
			$dataValue = $qualifier->getDataValue();
			$params['value'] = FormatJson::encode( $dataValue->getArrayValue() );
		}

		$this->makeValidRequest( $params );

		$entity = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $entityId );

		$claims = new Claims( $entity->getClaims() );

		$this->assertTrue( $claims->hasClaimWithGuid( $params['claim'] ) );

		$claim = $claims->getClaimWithGuid( $params['claim'] );

		$this->assertTrue(
			$claim->getQualifiers()->hasSnak( $qualifier ),
			'The qualifier should exist in the qualifier list after making the request'
		);
	}

	protected function makeValidRequest( array $params ) {
		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		return $resultArray;
	}

	/**
	 * @dataProvider invalidClaimProvider
	 */
	public function testInvalidClaimGuid( $claimGuid ) {
		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $claimGuid,
			'property' => 7,
			'snaktype' => 'value',
			'value' => 'abc',
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid claim guid did not throw an error' );
		} catch ( UsageException $e ) {
			$this->assertEquals( 'invalid-guid', $e->getCodeString(),  'Invalid claim guid raised correct error' );
		}
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ),
			array( 'x$y$z' )
		);
	}

}
