<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use PHPUnit\Framework\Constraint\Constraint;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\CreateClaim
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
 */
class CreateClaimTest extends WikibaseApiTestCase {

	protected function getNewItemAndProperty(): array {
		$store = $this->getEntityStore();

		$item = new Item();
		$store->saveEntity( $item, 'test', $this->user, EDIT_NEW );

		$property = Property::newFromType( 'string' );
		$store->saveEntity( $property, 'test', $this->user, EDIT_NEW );

		return [ $item, $property ];
	}

	protected function assertRequestValidity( array $resultArray ): void {
		$this->assertResultSuccess( $resultArray );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );
		$this->assertArrayNotHasKey( 'lastrevid', $resultArray['claim'], 'claim has a lastrevid key' );

		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'lastrevid', $resultArray['pageinfo'], 'pageinfo has a lastrevid key' );
	}

	public function testValidRequest() {
		/**
		 * @var Item $item
		 * @var Property $property
		 */
		list( $item, $property ) = $this->getNewItemAndProperty();

		$params = [
			'action' => 'wbcreateclaim',
			'entity' => $item->getId()->getSerialization(),
			'snaktype' => 'value',
			'property' => $property->getId()->getSerialization(),
			'value' => '"Foo"',
		];

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertRequestValidity( $resultArray );

		$claim = $resultArray['claim'];

		foreach ( [ 'id', 'mainsnak', 'type', 'rank' ] as $requiredKey ) {
			$this->assertArrayHasKey( $requiredKey, $claim, 'claim has a "' . $requiredKey . '" key' );
		}

		$this->assertStringStartsWith( $item->getId()->getSerialization(), $claim['id'] );

		$this->assertEquals( 'value', $claim['mainsnak']['snaktype'] );

		$item = WikibaseRepo::getEntityLookup()->getEntity( $item->getId() );

		$this->assertNotNull( $item->getStatements()->getFirstStatementWithGuid( $claim['id'] ) );
	}

	public function testCreateClaimWithTag() {
		/**
		 * @var Item $item
		 * @var Property $property
		 */
		list( $item, $property ) = $this->getNewItemAndProperty();

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbcreateclaim',
			'entity' => $item->getId()->getSerialization(),
			'snaktype' => 'value',
			'property' => $property->getId()->getSerialization(),
			'value' => '"Foo"',
		] );
	}

	public function testReturnsNormalizedData(): void {
		$itemId = $this->getEntityStore()
			->saveEntity( new Item(), 'test', $this->user, EDIT_NEW )
			->getEntity()
			->getId();
		$propertyId = $this->createUppercaseStringTestProperty();

		[ $response ] = $this->doApiRequestWithToken( [
			'action' => 'wbcreateclaim',
			'entity' => $itemId->getSerialization(),
			'property' => $propertyId->getSerialization(),
			'snaktype' => 'value',
			'value' => '"a string"',
		] );

		$this->assertSame( 'A STRING', $response['claim']['mainsnak']['datavalue']['value'] );
	}

	public function invalidRequestProvider(): iterable {
		$argLists = [];

		//0
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => 'q123456789',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '"Foo"',
		];
		$argLists[] = [ 'no-such-entity', $params ];

		//1
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => 'i123',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '"Foo"',
		];
		$argLists[] = [ 'invalid-entity-id', $params ];

		//2
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => 'i123',
			'value' => '"Foo"',
		];
		$argLists[] = [ 'invalid-entity-id', $params ];

		//3
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => 'p1',
			'value' => 'Foo',
		];
		$argLists[] = [ 'invalid-snak', $params ];

		//4
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'hax',
			'property' => '-',
			'value' => '"Foo"',
		];
		$argLists[] = [
			$this->logicalOr(
				$this->equalTo( 'unknown_snaktype' ),
				$this->equalTo( 'badvalue' )
			),
			$params,
		];

		//5, 6, 7
		foreach ( [ 'entity', 'snaktype', 'property' ] as $requiredParam ) {
			$params = [
				'action' => 'wbcreateclaim',
				'entity' => '-',
				'snaktype' => 'value',
				'property' => '-',
				'value' => '"Foo"',
			];

			unset( $params[$requiredParam] );

			$argLists[] = [
				$this->logicalOr(
					$this->equalTo( 'no' . $requiredParam ),
					$this->equalTo( 'missingparam' )
				),
				$params,
			];
		}

		//8
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
		];
		$argLists[] = [ 'param-missing', $params ];

		//9
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '{"x":"foo", "y":"bar"}',
		];
		$argLists[] = [ 'invalid-snak', $params ];

		//10
		$params = [
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '"   "', //blank is invalid
		];
		$argLists[] = [ 'modification-failed', $params ];

		return $argLists;
	}

	public function getItemAndPropertyForInvalid(): array {
		static $array = null;

		if ( $array === null ) {
			$array = $this->getNewItemAndProperty();
		}

		return $array;
	}

	/**
	 * @dataProvider invalidRequestProvider
	 *
	 * @param string|Constraint $errorCode
	 * @param array $params
	 */
	public function testInvalidRequest( $errorCode, array $params ) {
		/**
		 * @var Item $item
		 * @var Property $property
		 */
		list( $item, $property ) = $this->getItemAndPropertyForInvalid();

		if ( array_key_exists( 'entity', $params ) && $params['entity'] === '-' ) {
			$params['entity'] = $item->getId()->getSerialization();
		}

		if ( array_key_exists( 'property', $params ) && $params['property'] === '-' ) {
			$params['property'] = $property->getId()->getSerialization();
		}

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request should raise an exception' );
		} catch ( ApiUsageException $ex ) {
			$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
			$this->assertThat(
				$msg->getApiCode(),
				$errorCode instanceof Constraint ? $errorCode : $this->equalTo( $errorCode ),
				'Invalid request raised correct error: ' . $ex->getMessage()
			);
		}

		/** @var Item $obtainedItem */
		$obtainedItem = WikibaseRepo::getEntityLookup()->getEntity( $item->getId() );

		$this->assertTrue( $obtainedItem->getStatements()->isEmpty() );
	}

	public function testMultipleRequests() {
		/**
		 * @var Item $item
		 * @var Property $property
		 */
		list( $item, $property ) = $this->getNewItemAndProperty();

		$params = [
			'action' => 'wbcreateclaim',
			'entity' => $item->getId()->getSerialization(),
			'snaktype' => 'value',
			'property' => $property->getId()->getSerialization(),
			'value' => '"Foo"',
		];

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertRequestValidity( $resultArray );

		$revId = $resultArray['pageinfo']['lastrevid'];

		$firstGuid = $resultArray['claim']['id'];

		$params = [
			'action' => 'wbcreateclaim',
			'entity' => $item->getId()->getSerialization(),
			'snaktype' => 'value',
			'property' => $property->getId()->getSerialization(),
			'value' => '"Bar"',
			'baserevid' => $revId,
		];

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertRequestValidity( $resultArray );

		$newRevId = $resultArray['pageinfo']['lastrevid'];

		$secondGuid = $resultArray['claim']['id'];

		$this->assertGreaterThan( $revId, $newRevId );

		$this->assertNotEquals( $firstGuid, $secondGuid );

		$item = WikibaseRepo::getEntityLookup()->getEntity( $item->getId() );

		$this->assertNotNull( $item->getStatements()->getFirstStatementWithGuid( $firstGuid ) );
		$this->assertNotNull( $item->getStatements()->getFirstStatementWithGuid( $secondGuid ) );
	}

}
