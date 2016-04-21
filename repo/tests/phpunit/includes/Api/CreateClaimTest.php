<?php

namespace Wikibase\Test\Repo\Api;

use UsageException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\CreateClaim
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group CreateClaimTest
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CreateClaimTest extends WikibaseApiTestCase {

	protected static function getNewItemAndProperty() {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$item = new Item();
		$store->saveEntity( $item, 'test', $GLOBALS['wgUser'], EDIT_NEW );

		$property = Property::newFromType( 'commonsMedia' );
		$store->saveEntity( $property, 'test', $GLOBALS['wgUser'], EDIT_NEW );

		return array( $item, $property );
	}

	protected function assertRequestValidity( $resultArray ) {
		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
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
		list( $item, $property ) = self::getNewItemAndProperty();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $item->getId()->getSerialization(),
			'snaktype' => 'value',
			'property' => $property->getId()->getSerialization(),
			'value' => '"Foo.png"',
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertRequestValidity( $resultArray );

		$claim = $resultArray['claim'];

		foreach ( array( 'id', 'mainsnak', 'type', 'rank' ) as $requiredKey ) {
			$this->assertArrayHasKey( $requiredKey, $claim, 'claim has a "' . $requiredKey . '" key' );
		}

		$this->assertStringStartsWith( $item->getId()->getSerialization(), $claim['id'] );

		$this->assertEquals( 'value', $claim['mainsnak']['snaktype'] );

		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $item->getId() );

		$this->assertNotNull( $item->getStatements()->getFirstStatementWithGuid( $claim['id'] ) );
	}

	public function invalidRequestProvider() {
		$argLists = [];

		//0
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => 'q123456789',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '"Foo.png"',
		);
		$argLists[] = array( 'cant-load-entity-content', $params );

		//1
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => 'i123',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '"Foo.png"',
		);
		$argLists[] = array( 'invalid-entity-id', $params );

		//2
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => 'i123',
			'value' => '"Foo.png"',
		);
		$argLists[] = array( 'invalid-entity-id', $params );

		//3
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => 'p1',
			'value' => 'Foo.png',
		);
		$argLists[] = array( 'invalid-snak', $params );

		//4
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'hax',
			'property' => '-',
			'value' => '"Foo.png"',
		);
		$argLists[] = array( 'unknown_snaktype', $params );

		//5, 6
		foreach ( array( 'entity', 'snaktype' ) as $requiredParam ) {
			$params = array(
				'action' => 'wbcreateclaim',
				'entity' => '-',
				'snaktype' => 'value',
				'property' => '-',
				'value' => '"Foo.png"',
			);

			unset( $params[$requiredParam] );

			$argLists[] = array( 'no' . $requiredParam, $params );
		}

		//7
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'value' => '"Foo.png"',
		);
		$argLists[] = array( 'param-missing', $params );

		//8
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
		);
		$argLists[] = array( 'param-missing', $params );

		//9
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '{"x":"foo", "y":"bar"}',
		);
		$argLists[] = array( 'invalid-snak', $params );

		//10
		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '"   "', //blank is invalid
		);
		$argLists[] = array( 'modification-failed', $params );

		return $argLists;
	}

	public static function getItemAndPropertyForInvalid() {
		static $array = null;

		if ( $array === null ) {
			$array = self::getNewItemAndProperty();
		}

		return $array;
	}

	/**
	 * @dataProvider invalidRequestProvider
	 *
	 * @param string $errorCode
	 * @param array $params
	 */
	public function testInvalidRequest( $errorCode, array $params ) {
		/**
		 * @var Item $item
		 * @var Property $property
		 */
		list( $item, $property ) = self::getItemAndPropertyForInvalid();

		if ( array_key_exists( 'entity', $params ) && $params['entity'] === '-' ) {
			$params['entity'] = $item->getId()->getSerialization();
		}

		if ( array_key_exists( 'property', $params ) && $params['property'] === '-' ) {
			$params['property'] = $property->getId()->getSerialization();
		}

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request should raise an exception' );
		} catch ( UsageException $ex ) {
			$this->assertEquals(
				$errorCode,
				$ex->getCodeString(), 'Invalid request raised correct error: ' . $ex->getMessage()
			);
		}

		/** @var Item $obtainedItem */
		$obtainedItem = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $item->getId() );

		$this->assertTrue( $obtainedItem->getStatements()->isEmpty() );
	}

	public function testMultipleRequests() {
		/**
		 * @var Item $item
		 * @var Property $property
		 */
		list( $item, $property ) = self::getNewItemAndProperty();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $item->getId()->getSerialization(),
			'snaktype' => 'value',
			'property' => $property->getId()->getSerialization(),
			'value' => '"Foo.png"',
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertRequestValidity( $resultArray );

		$revId = $resultArray['pageinfo']['lastrevid'];

		$firstGuid = $resultArray['claim']['id'];

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $item->getId()->getSerialization(),
			'snaktype' => 'value',
			'property' => $property->getId()->getSerialization(),
			'value' => '"Bar.jpg"',
			'baserevid' => $revId
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertRequestValidity( $resultArray );

		$newRevId = $resultArray['pageinfo']['lastrevid'];

		$secondGuid = $resultArray['claim']['id'];

		$this->assertGreaterThan( $revId, $newRevId );

		$this->assertNotEquals( $firstGuid, $secondGuid );

		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $item->getId() );

		$this->assertNotNull( $item->getStatements()->getFirstStatementWithGuid( $firstGuid ) );
		$this->assertNotNull( $item->getStatements()->getFirstStatementWithGuid( $secondGuid ) );
	}

}
