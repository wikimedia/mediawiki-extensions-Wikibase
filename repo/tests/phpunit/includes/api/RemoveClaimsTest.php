<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
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

		foreach( $statements as $statement ){
			$guidGenerator = new ClaimGuidGenerator();
			$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
			$item->addClaim( $statement );
		}

		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

		return $item;
	}

	public function entityProvider() {
		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setLabel( 'en', 'kittens' );

		$nonEmptyItem = Item::newEmpty();
		$nonEmptyItem->setFingerprint( $fingerprint );

		return array(
			$this->addStatementsAndSave( Item::newEmpty() ),
			$this->addStatementsAndSave( $nonEmptyItem ),
		);
	}

	public function testValidRequests() {
		foreach ( $this->entityProvider() as $entity ) {
			$this->doTestValidRequestSingle( $entity );
		}

		foreach ( $this->entityProvider() as $entity ) {
			$this->doTestValidRequestMultiple( $entity );
		}
	}

	/**
	 * @param Entity $entity
	 */
	public function doTestValidRequestSingle( Entity $entity ) {
		$obtainedClaims = null;

		/**
		 * @var Claim[] $claims
		 */
		$claims = $entity->getClaims();
		while ( $claim = array_shift( $claims ) ) {
			$this->makeTheRequest( array( $claim->getGuid() ) );

			$entity = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $entity->getId() );
			$obtainedClaims = new Claims( $entity->getClaims() );

			$this->assertFalse( $obtainedClaims->hasClaimWithGuid( $claim->getGuid() ) );

			$currentClaims = new Claims( $claims );

			$this->assertTrue( $obtainedClaims->getHash() === $currentClaims->getHash() );
		}

		$this->assertTrue( $obtainedClaims === null || $obtainedClaims->isEmpty() );
	}

	/**
	 * @param Entity $entity
	 */
	public function doTestValidRequestMultiple( Entity $entity ) {
		$guids = array();

		/**
		 * @var Claim $claim
		 */
		foreach ( $entity->getClaims() as $claim ) {
			$guids[] = $claim->getGuid();
		}

		$this->makeTheRequest( $guids );

		$obtainedEntity = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $entity->getId() );

		$this->assertFalse( $obtainedEntity->hasClaims() );
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
