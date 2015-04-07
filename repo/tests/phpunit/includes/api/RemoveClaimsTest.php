<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\ClaimGuidGenerator;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\RemoveClaims
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group RemoveClaimsTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RemoveClaimsTest extends WikibaseApiTestCase {

	private static $propertyId;

	/**
	 * @param Item $item
	 *
	 * @return Item
	 */
	private function addStatementsAndSave( Item $item ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

		if ( !isset( self::$propertyId ) ) {
			self::$propertyId = $this->getNewProperty( 'string' )->getId();
		}

		/** @var $statements Statement[] */
		$statements = array(
			new Statement( new Claim( new PropertyNoValueSnak( self::$propertyId ) ) ),
			new Statement( new Claim( new PropertyNoValueSnak( self::$propertyId ) ) ),
			new Statement( new Claim( new PropertySomeValueSnak( self::$propertyId ) ) ),
			new Statement( new Claim( new PropertyValueSnak( self::$propertyId, new StringValue( 'o_O' ) ) ) ),
		);

		foreach ( $statements as $statement ) {
			$guidGenerator = new ClaimGuidGenerator();
			$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
			$item->addClaim( $statement );
		}

		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

		return $item;
	}

	/**
	 * @return Item[]
	 */
	public function itemProvider() {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'kittens' );

		$nonEmptyItem = new Item();
		$nonEmptyItem->setFingerprint( $fingerprint );

		return array(
			$this->addStatementsAndSave( new Item() ),
			$this->addStatementsAndSave( $nonEmptyItem ),
		);
	}

	public function testValidRequests() {
		foreach ( $this->itemProvider() as $item ) {
			$this->doTestValidRequestSingle( $item );
		}

		foreach ( $this->itemProvider() as $item ) {
			$this->doTestValidRequestMultiple( $item );
		}
	}

	/**
	 * @param Item $item
	 */
	public function doTestValidRequestSingle( Item $item ) {
		$statements = $item->getStatements()->toArray();
		$obtainedStatements = null;

		while ( $statement = array_shift( $statements ) ) {
			$this->makeTheRequest( array( $statement->getGuid() ) );

			/** @var Item $obtainedItem */
			$obtainedItem = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $item->getId() );
			$obtainedStatements = $obtainedItem->getStatements();

			$obtainedClaims = new Claims( $obtainedStatements->toArray() );
			$this->assertFalse( $obtainedClaims->hasClaimWithGuid( $statement->getGuid() ) );

			$currentStatements = new StatementList( $statements );

			$this->assertTrue( $obtainedStatements->equals( $currentStatements ) );
		}

		$this->assertTrue( $obtainedStatements === null || $obtainedStatements->isEmpty() );
	}

	/**
	 * @param Item $item
	 */
	public function doTestValidRequestMultiple( Item $item ) {
		$guids = array();

		/** @var Statement $statement */
		foreach ( $item->getStatements() as $statement ) {
			$guids[] = $statement->getGuid();
		}

		$this->makeTheRequest( $guids );

		/** @var Item $obtainedItem */
		$obtainedItem = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $item->getId() );

		$this->assertTrue( $obtainedItem->getStatements()->isEmpty() );
	}

	private function makeTheRequest( array $claimGuids ) {
		$params = array(
			'action' => 'wbremoveclaims',
			'claim' => implode( '|', $claimGuids ),
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claims', $resultArray, 'top level element has a claims key' );

		$claims = $resultArray['claims'];

		$this->assertInternalType( 'array', $claims, 'top claims element is an array' );

		$this->assertArrayEquals( $claimGuids, $claims );
	}

	/**
	 * @expectedException \UsageException
	 *
	 * @dataProvider invalidClaimProvider
	 */
	public function testRemoveInvalidClaims( $claimGuids ) {
		$params = array(
			'action' => 'wbremoveclaims',
			'claim' => is_array( $claimGuids ) ? implode( '|', $claimGuids ) : $claimGuids,
		);

		$this->doApiRequestWithToken( $params );
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ), //wrong guid
			array( 'x$y$z' ), //wrong guid
		);
	}

	/**
	 * @param string $type
	 *
	 * @return Property
	 */
	private function getNewProperty( $type ) {
		$property = Property::newFromType( $type );

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $property, '', $GLOBALS['wgUser'], EDIT_NEW );

		return $property;
	}

}
