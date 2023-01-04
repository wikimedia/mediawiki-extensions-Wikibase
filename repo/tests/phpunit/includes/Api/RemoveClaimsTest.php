<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\RemoveClaims
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RemoveClaimsTest extends WikibaseApiTestCase {

	private static $propertyId;

	private function addStatementsAndSave( Item $item ): Item {
		$store = $this->getEntityStore();
		$store->saveEntity( $item, '', $this->user, EDIT_NEW );

		if ( !isset( self::$propertyId ) ) {
			self::$propertyId = $this->getNewProperty( 'string' )->getId();
		}

		/** @var Statement[] $statements */
		$statements = [
			new Statement( new PropertyNoValueSnak( self::$propertyId ) ),
			new Statement( new PropertyNoValueSnak( self::$propertyId ) ),
			new Statement( new PropertySomeValueSnak( self::$propertyId ) ),
			new Statement( new PropertyValueSnak( self::$propertyId, new StringValue( 'o_O' ) ) ),
		];

		$guidGenerator = new GuidGenerator();
		$itemStatements = $item->getStatements();
		foreach ( $statements as $statement ) {
			$statement->setGuid( $guidGenerator->newGuid( $item->getId() ) );
			$itemStatements->addStatement( $statement );
		}

		$store->saveEntity( $item, '', $this->user, EDIT_UPDATE );

		return $item;
	}

	/**
	 * @return Item[]
	 */
	public function itemProvider(): iterable {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'kittens' );

		$nonEmptyItem = new Item();
		$nonEmptyItem->setFingerprint( $fingerprint );

		return [
			$this->addStatementsAndSave( new Item() ),
			$this->addStatementsAndSave( $nonEmptyItem ),
		];
	}

	public function testValidRequests() {
		$this->overrideConfigValue( 'RateLimits',
			[ 'edit' => [ '&can-bypass' => true, 'user' => [ 1000, 60 ] ] ] );
		foreach ( $this->itemProvider() as $item ) {
			$this->doTestValidRequestSingle( $item );
		}

		foreach ( $this->itemProvider() as $item ) {
			$this->doTestValidRequestMultiple( $item );
		}
	}

	public function testRemoveClaimsWithTag() {
		$item = $this->addStatementsAndSave( new Item() );
		$statements = $item->getStatements()->toArray();
		$statement = array_shift( $statements );
		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbremoveclaims',
			'claim' => $statement->getGuid(),
		] );
	}

	public function doTestValidRequestSingle( Item $item ) {
		$statements = $item->getStatements()->toArray();
		$obtainedStatements = null;

		while ( true ) {
			$statement = array_shift( $statements );
			if ( !$statement ) {
				break;
			}

			$this->makeTheRequest( [ $statement->getGuid() ] );

			/** @var Item $obtainedItem */
			$obtainedItem = WikibaseRepo::getEntityLookup()->getEntity( $item->getId() );
			$obtainedStatements = $obtainedItem->getStatements();

			$this->assertNull( $obtainedStatements->getFirstStatementWithGuid( $statement->getGuid() ) );

			$currentStatements = new StatementList( ...$statements );

			$this->assertTrue( $obtainedStatements->equals( $currentStatements ) );
		}

		$this->assertTrue( $obtainedStatements === null || $obtainedStatements->isEmpty() );
	}

	public function doTestValidRequestMultiple( Item $item ) {
		$guids = [];

		/** @var Statement $statement */
		foreach ( $item->getStatements() as $statement ) {
			$guids[] = $statement->getGuid();
		}

		$this->makeTheRequest( $guids );

		/** @var Item $obtainedItem */
		$obtainedItem = WikibaseRepo::getEntityLookup()->getEntity( $item->getId() );

		$this->assertTrue( $obtainedItem->getStatements()->isEmpty() );
	}

	private function makeTheRequest( array $claimGuids ): void {
		$params = [
			'action' => 'wbremoveclaims',
			'claim' => implode( '|', $claimGuids ),
		];

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertResultSuccess( $resultArray );
		$this->assertIsArray( $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claims', $resultArray, 'top level element has a claims key' );

		$claims = $resultArray['claims'];

		$this->assertIsArray( $claims, 'top claims element is an array' );

		$this->assertArrayEquals( $claimGuids, $claims );
	}

	/** @dataProvider invalidClaimProvider */
	public function testRemoveInvalidClaims( string $claimGuid ) {
		$params = [
			'action' => 'wbremoveclaims',
			'claim' => $claimGuid,
		];

		$this->expectException( ApiUsageException::class );
		$this->doApiRequestWithToken( $params );
	}

	public function invalidClaimProvider(): iterable {
		return [
			[ 'xyz' ], //wrong guid
			[ 'x$y$z' ], //wrong guid
		];
	}

	private function getNewProperty( string $type ): Property {
		$property = Property::newFromType( $type );

		$store = $this->getEntityStore();
		$store->saveEntity( $property, '', $this->user, EDIT_NEW );

		return $property;
	}

}
