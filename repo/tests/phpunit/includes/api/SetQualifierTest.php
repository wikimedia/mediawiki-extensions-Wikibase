<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\PropertyValueSnak;
use Wikibase\Reference;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Snak;
use Wikibase\SnakList;
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
	 * @return Snak[]
	 */
	protected function snakProvider() {
		static $hasProperties = false;

		$prop42 = new PropertyId( 'p42' );
		$prop9001 = new PropertyId( 'p9001' );
		$prop7201010 = new PropertyId( 'p7201010' );

		if ( !$hasProperties ) {
			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop42 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop9001 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop7201010 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$hasProperties = true;
		}

		$snaks = array();

		$snaks[] = new PropertyNoValueSnak( $prop42 );
		$snaks[] = new PropertySomeValueSnak( $prop9001 );
		$snaks[] = new PropertyValueSnak( $prop7201010, new StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Claim[]
	 */
	protected function claimProvider() {
		$statements = array();

		$mainSnak = new PropertyNoValueSnak( 42 );
		$statement = new Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $this->snakProvider() as $snak ) {
			$statement = clone $statement;
			$snaks = new SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new Reference( $snaks ) );
		$statements[] = $statement;

		$ranks = array(
			Statement::RANK_DEPRECATED,
			Statement::RANK_NORMAL,
			Statement::RANK_PREFERRED
		);

		/**
		 * @var Statement[] $statements
		 */
		foreach ( $statements as &$statement ) {
			$statement->setRank( $ranks[array_rand( $ranks )] );
		}

		return $statements;
	}

	/**
	 * @return Snak[]
	 */
	protected function newQualifierProvider() {
		$properties = array();

		$property1 = Property::newFromType( 'commonsMedia' );
		$properties[] = $property1;

		$property2 = Property::newFromType( 'wikibase-item' );
		$properties[] = $property2;

		foreach( $properties as $property ) {
			$content = new \Wikibase\PropertyContent( $property );
			$status = $content->save( '', null, EDIT_NEW );

			$this->assertTrue( $status->isOK() );
		}

		return array(
			new PropertySomeValueSnak( 9001 ),
			new PropertyNoValueSnak( 9001 ),
			new PropertyValueSnak( $property1->getId(), new StringValue( 'Dummy.jpg' ) ),
			new PropertyValueSnak( $property2->getId(), new EntityIdValue( new ItemId( 'q802' ) ) ),
		);
	}

	public function testRequests() {
		foreach( $this->claimProvider() as $claim ) {
			$item = \Wikibase\Item::newEmpty();
			$item->setId( new ItemId( 'q802' ) );
			$content = new \Wikibase\ItemContent( $item );
			$content->save( '', null, EDIT_NEW );

			$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
			$claim->setGuid( $guidGenerator->newGuid() );
			$item->addClaim( $claim );

			$content->save( '' );

			// This qualifier should not be part of the Claim yet!
			foreach ( $this->newQualifierProvider() as $qualifier ) {
				$this->makeAddRequest( $claim->getGuid(), $qualifier, $item->getId() );
			}
		}
	}

	protected function makeAddRequest( $statementGuid, Snak $qualifier, EntityId $entityId ) {
		$entityIdFormatter = WikibaseRepo::getDefaultInstance()->getEntityIdFormatter();

		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $statementGuid,
			'snaktype' => $qualifier->getType(),
			'property' => $entityIdFormatter->format( $qualifier->getPropertyId() ),
		);

		if ( $qualifier instanceof PropertyValueSnak ) {
			$dataValue = $qualifier->getDataValue();
			$params['value'] = \FormatJson::encode( $dataValue->getArrayValue() );
		}

		$this->makeValidRequest( $params );

		$content = \Wikibase\EntityContentFactory::singleton()->getFromId( $entityId );

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

	// TODO: test update requests


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
