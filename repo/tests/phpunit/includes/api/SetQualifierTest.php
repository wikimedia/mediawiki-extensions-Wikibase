<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Snak;
use Wikibase\Statement;
use Wikibase\Claim;

/**
 * @covers Wikibase\Api\SetQualifier
 *
 * @file
 * @since 0.3
 *
 * @ingroup WikibaseRepoTest
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
 */
class SetQualifierTest extends WikibaseApiTestCase {

	/**
	 * Creates the given property in the database, if necessary.
	 *
	 * @param \Wikibase\DataModel\Entity\PropertyId $id
	 * @param $type
	 *
	 * @return Property
	 */
	protected function makeProperty( PropertyId $id, $type ) {
		static $properties = array();

		$key = $id->getPrefixedId();

		if ( !isset( $properties[$key] ) ) {
			$prop = PropertyContent::newEmpty();
			$prop->getProperty()->setId( $id );
			$prop->getProperty()->setDataTypeId( $type );
			$prop->save( 'testing' );

			$properties[$key] = $prop->getProperty();
		}

		return $properties[$key];
	}


	protected function getTestItem() {
		static $item = null;

		if ( !$item ) {
			$item = \Wikibase\Item::newEmpty();
			$item->setId( new ItemId( 'q802' ) );

			$content = $content = \Wikibase\ItemContent::newfromItem( $item );
			$content->save( '', null, EDIT_NEW );

			$prop114id = new PropertyId( 'p114' );
			$prop114 = $this->makeProperty( $prop114id, 'string' );
			$claim = new Statement( new PropertyValueSnak( $prop114id, new StringValue( '^_^' ) ) );

			$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
			$claim->setGuid( $guidGenerator->newGuid() );
			$item->addClaim( $claim );

			$content->save( '' );
		}

		return $item;
	}


	public function provideAddRequests() {
		$prop42 = new PropertyId( 'p42' );
		$prop9001 = new PropertyId( 'p9001' );
		$prop7201010 = new PropertyId( 'p7201010' );

		$prop = PropertyContent::newEmpty();
		$prop->getEntity()->setId( $prop42 );
		$prop->getEntity()->setDataTypeId( 'string' );

		$prop = PropertyContent::newEmpty();
		$prop->getEntity()->setId( $prop9001 );
		$prop->getEntity()->setDataTypeId( 'string' );

		$prop = PropertyContent::newEmpty();
		$prop->getEntity()->setId( $prop7201010 );
		$prop->getEntity()->setDataTypeId( 'string' );

		$cases = array();

		$cases['p42'] = array( new PropertyNoValueSnak( $prop42 ), 'string' );
		$cases['p9001'] = array( new PropertySomeValueSnak( $prop9001 ), 'string' );
		$cases['p7201010'] = array( new PropertyValueSnak( $prop7201010, new StringValue( 'o_O' ) ), 'string' );

		return $cases;
	}

	/**
	 * @dataProvider provideAddRequests
	 */
	public function testAddRequests( Snak $snak, $type ) {
		$item = $this->getTestItem();
		$claims = $item->getClaims();
		$claim = reset( $claims );

		$prop = $snak->getPropertyId();
		$this->makeProperty( $prop, $type );

		$this->makeSetQualifierRequest( $claim->getGuid(), null, $snak, $item->getId() );

		// now the hash exists, so the same request should fail
		$this->setExpectedException( 'UsageException' );
		$this->makeSetQualifierRequest( $claim->getGuid(), null, $snak, $item->getId() );
	}

	public function provideChangeRequests() {
		$cases = array_filter(
			$this->provideAddRequests(),
			function ( $case ) {
				return $case[0] instanceof PropertyValueSnak;
			}
		);

		return $cases;
	}

	/**
	 * @dataProvider provideChangeRequests
	 */
	public function testChangeRequests( Snak $snak, $type ) {
		$item = $this->getTestItem();
		$claims = $item->getClaims();
		$claim = reset( $claims );

		$prop = $snak->getPropertyId();
		$this->makeProperty( $prop, $type );

		static $counter = 1;
		$hash = $snak->getHash();
		$newQualifier = new PropertyValueSnak( $snak->getPropertyId(), new StringValue( __METHOD__ . '#' . $counter++ ) );

		$this->makeSetQualifierRequest( $claim->getGuid(), $hash, $newQualifier, $item->getId() );

		// now the hash changed, so the same request should fail
		$this->setExpectedException( 'UsageException' );
		$this->makeSetQualifierRequest( $claim->getGuid(), $hash, $newQualifier, $item->getId() );
	}

	protected function makeSetQualifierRequest( $statementGuid, $snakhash, Snak $qualifier, EntityId $entityId ) {
		$entityIdFormatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();

		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $statementGuid,
			'snakhash' => $snakhash,
			'snaktype' => $qualifier->getType(),
			'property' => $entityIdFormatter->format( $qualifier->getPropertyId() ),
		);

		if ( $qualifier instanceof PropertyValueSnak ) {
			$dataValue = $qualifier->getDataValue();
			$params['value'] = \FormatJson::encode( $dataValue->getArrayValue() );
		}

		$this->makeValidRequest( $params );

		$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getFromId( $entityId );

		$this->assertInstanceOf( '\Wikibase\EntityContent', $content );

		$claims = new \Wikibase\Claims( $content->getEntity()->getClaims() );

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
		} catch ( \UsageException $e ) {
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
