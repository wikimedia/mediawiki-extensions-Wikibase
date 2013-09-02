<?php

namespace Wikibase\Test\Api;

use Wikibase\Entity;
use Wikibase\Claim;
use Wikibase\EntityId;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\SetClaimValue
 *
 * @since 0.3
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetClaimValueTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SetClaimValueTest extends WikibaseApiTestCase {

	/**
	 * @param Entity $entity
	 * @param EntityId $propertyId
	 *
	 * @return Entity
	 */
	protected function addClaimsAndSave( Entity $entity, EntityId $propertyId ) {
		$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->newFromEntity( $entity );
		$content->save( '', null, EDIT_NEW );

		$claim = $entity->newClaimBase( new \Wikibase\PropertyValueSnak( $propertyId, new \DataValues\StringValue( 'o_O' ) ) );
		$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P' );
		$entity->addClaim( $claim );

		$content->save( '' );

		return $content->getEntity();
	}

	/**
	 * @param EntityId $propertyId
	 *
	 * @return Entity[]
	 */
	protected function getEntities( EntityId $propertyId ) {
		$property = \Wikibase\Property::newEmpty();
		$property->setDataTypeId( 'string' );

		return array(
			$this->addClaimsAndSave( \Wikibase\Item::newEmpty(), $propertyId ),
			$this->addClaimsAndSave( $property,$propertyId ),
		);
	}

	public function testValidRequests() {
		$argLists = array();

		$property = \Wikibase\Property::newFromType( 'commonsMedia' );
		$content = new \Wikibase\PropertyContent( $property );
		$content->save( '', null, EDIT_NEW );
		$property = $content->getEntity();

		foreach( $this->getEntities( $property->getId() ) as $entity ) {
			/**
			 * @var Claim $claim
			 */
			foreach ( $entity->getClaims() as $claim ) {
				$argLists[] = array( $entity, $claim->getGuid(), 'Kittens.png' );
			}
		}

		foreach ( $argLists as $argList ) {
			call_user_func_array( array( $this, 'doTestValidRequest' ), $argList );
		}
	}

	public function doTestValidRequest( Entity $entity, $claimGuid, $value ) {
		$content = \Wikibase\EntityContentFactory::singleton()->getFromId( $entity->getId() );
		$claimCount = count( $content->getEntity()->getClaims() );

		$params = array(
			'action' => 'wbsetclaimvalue',
			'claim' => $claimGuid,
			'value' => \FormatJson::encode( $value ),
			'snaktype' => 'value',
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );

		$claim = $resultArray['claim'];

		$this->assertEquals( $value, $claim['mainsnak']['datavalue']['value'] );

		$content = \Wikibase\EntityContentFactory::singleton()->getFromId( $entity->getId() );
		$obtainedEntity = $content->getEntity();

		$claims = new \Wikibase\Claims( $obtainedEntity->getClaims() );

		$this->assertEquals( $claimCount, $claims->count(), 'Claim count should not change after doing a setclaimvalue request' );

		$this->assertTrue( $claims->hasClaimWithGuid( $claimGuid ) );

		$dataValue = \DataValues\DataValueFactory::singleton()->newFromArray( $claim['mainsnak']['datavalue'] );

		$this->assertTrue( $claims->getClaimWithGuid( $claimGuid )->getMainSnak()->getDataValue()->equals( $dataValue ) );
	}

	/**
	 * @dataProvider invalidClaimProvider
	 */
	public function testInvalidClaimGuid( $claimGuid ) {
		$params = array(
			'action' => 'wbsetclaimvalue',
			'claim' => $claimGuid,
			'snaktype' => 'value',
			'value' => 'abc',
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid claim guid did not raise an error' );
		} catch ( \UsageException $e ) {
			$this->assertEquals( 'invalid-guid', $e->getCodeString(),  'Invalid claim guid raised correct error' );
		}
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ),
			array( 'x$y$z' ),
			array( 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f' )
		);
	}

}
