<?php

namespace Wikibase\Test\Api;
use Wikibase\Entity;
use Wikibase\Claim;
use Wikibase\EntityId;

/**
 * Unit tests for the Wikibase\class ApiSetClaimValue class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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
 * @group SetClaimValueTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SetClaimValueTest extends \ApiTestCase {

	/**
	 * @param Entity $entity
	 * @param EntityId $propertyId
	 *
	 * @return Entity
	 */
	protected function addClaimsAndSave( Entity $entity, EntityId $propertyId ) {
		$content = \Wikibase\EntityContentFactory::singleton()->newFromEntity( $entity );
		$content->save( '', null, EDIT_NEW );

		$entity->addClaim( $entity->newClaim( new \Wikibase\PropertyValueSnak( $propertyId, new \DataValues\StringValue( 'o_O' ) ) ) );

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
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

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
		$caughtException = false;

		$params = array(
			'action' => 'wbsetclaimvalue',
			'claim' => $claimGuid,
			'snaktype' => 'value',
			'value' => 'abc',
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		try {
			$this->doApiRequest( $params );
		} catch ( \UsageException $e ) {
			$this->assertEquals( 'invalid-guid', $e->getCodeString(),  'Invalid claim guid raised correct error' );
			$caughtException = true;
		}

		$this->assertTrue( $caughtException, 'Exception was caught' );
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ),
			array( 'x$y$z' )
		);
	}

}
