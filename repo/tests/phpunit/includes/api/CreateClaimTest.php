<?php

namespace Wikibase\Test\Api;
use Wikibase\Entity;
use Wikibase\Repo\WikibaseRepo;

/**
 * Unit tests for the Wikibase\ApiCreateClaim class.
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
 * @group CreateClaimTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class CreateClaimTest extends \ApiTestCase {

	protected static function getNewEntityAndProperty() {
		$entity = \Wikibase\Item::newEmpty();
		$content = new \Wikibase\ItemContent( $entity );
		$content->save( '', null, EDIT_NEW );
		$entity = $content->getEntity();

		$property = \Wikibase\Property::newFromType( 'commonsMedia' );
		$content = new \Wikibase\PropertyContent( $property );
		$content->save( '', null, EDIT_NEW );
		$property = $content->getEntity();

		return array( $entity, $property );
	}

	protected function assertRequestValidity( $resultArray ) {
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );
		$this->assertArrayNotHasKey( 'lastrevid', $resultArray['claim'], 'claim has a lastrevid key' );

		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'lastrevid', $resultArray['pageinfo'], 'pageinfo has a lastrevid key' );
	}

	public function testValidRequest() {
		/**
		 * @var Entity $entity
		 * @var Entity $property
		 */
		list( $entity, $property ) = self::getNewEntityAndProperty();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $this->getFormattedIdForEntity( $entity ),
			'snaktype' => 'value',
			'property' => $this->getFormattedIdForEntity( $property ),
			'value' => '"Foo.png"',
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertRequestValidity( $resultArray );

		$claim = $resultArray['claim'];

		foreach ( array( 'id', 'mainsnak', 'type', 'rank' ) as $requiredKey ) {
			$this->assertArrayHasKey( $requiredKey, $claim, 'claim has a "' . $requiredKey . '" key' );
		}

		$entityId = \Wikibase\Entity::getIdFromClaimGuid( $claim['id'] );

		$this->assertEquals( $this->getFormattedIdForEntity( $entity ), $entityId );

		$this->assertEquals( 'value', $claim['mainsnak']['snaktype'] );

		$entityContent = \Wikibase\EntityContentFactory::singleton()->getFromId( $entity->getId() );

		$claims = new \Wikibase\Claims( $entityContent->getEntity()->getClaims() );

		$this->assertTrue( $claims->hasClaimWithGuid( $claim['id'] ) );
	}

	public function invalidRequestProvider() {
		$argLists = array();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => 'q0',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '"Foo.png"',
		);

		$argLists[] = array( 'cant-load-entity-content', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => 'q0',
			'value' => '"Foo.png"',
		);

		$argLists[] = array( 'invalid-snak', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'hax',
			'property' => '-',
			'value' => '"Foo.png"',
		);

		$argLists[] = array( 'unknown_snaktype', $params );

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

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'value' => '"Foo.png"',
		);

		$argLists[] = array( 'claim-property-id-missing', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
		);

		$argLists[] = array( 'claim-value-missing', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
			'value' => '{"x":"foo", "y":"bar"}',
		);

		$argLists[] = array( 'invalid-snak', $params );

		return $argLists;
	}

	public static function getEntityAndPropertyForInvalid() {
		static $array = null;

		if ( $array === null ) {
			$array = self::getNewEntityAndProperty();
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
		 * @var Entity $entity
		 * @var Entity $property
		 */
		list( $entity, $property ) = self::getEntityAndPropertyForInvalid();

		$params['token'] = $GLOBALS['wgUser']->getEditToken();

		if ( array_key_exists( 'entity', $params ) && $params['entity'] === '-' ) {
			$params['entity'] = $this->getFormattedIdForEntity( $entity );
		}

		if ( array_key_exists( 'property', $params ) && $params['property'] === '-' ) {
			$params['property'] = $this->getFormattedIdForEntity( $entity );
		}

		try {
			$this->doApiRequest( $params );
			$this->assertFalse( true, 'Invalid request should raise an exception' );
		}
		catch ( \UsageException $e ) {
			$this->assertEquals( $errorCode, $e->getCodeString(), 'Invalid request raised correct error' );
		}

		$entityContent = \Wikibase\EntityContentFactory::singleton()->getFromId( $entity->getId() );

		$this->assertFalse( $entityContent->getEntity()->hasClaims() );
	}

	protected function getFormattedIdForEntity( Entity $entity ) {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		return $idFormatter->format( $entity->getId() );
	}

	public function testMultipleRequests() {
		/**
		 * @var Entity $entity
		 * @var Entity $property
		 */
		list( $entity, $property ) = self::getNewEntityAndProperty();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $this->getFormattedIdForEntity( $entity ),
			'snaktype' => 'value',
			'property' => $this->getFormattedIdForEntity( $property ),
			'value' => '"Foo.png"',
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertRequestValidity( $resultArray );

		$revId = $resultArray['pageinfo']['lastrevid'];

		$firstGuid = $resultArray['claim']['id'];

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $this->getFormattedIdForEntity( $entity ),
			'snaktype' => 'value',
			'property' => $this->getFormattedIdForEntity( $property ),
			'value' => '"Bar.jpg"',
			'token' => $GLOBALS['wgUser']->getEditToken(),
			'baserevid' => $revId
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertRequestValidity( $resultArray );

		$newRevId = $resultArray['pageinfo']['lastrevid'];

		$secondGuid = $resultArray['claim']['id'];

		$this->assertTrue( (int)$revId < (int)$newRevId );

		$this->assertNotEquals( $firstGuid, $secondGuid );

		$entityContent = \Wikibase\EntityContentFactory::singleton()->getFromId( $entity->getId() );

		$claims = new \Wikibase\Claims( $entityContent->getEntity()->getClaims() );

		$this->assertTrue( $claims->hasClaimWithGuid( $firstGuid ) );
		$this->assertTrue( $claims->hasClaimWithGuid( $secondGuid ) );
	}

}
