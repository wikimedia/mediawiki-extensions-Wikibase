<?php

namespace Wikibase\Test;
use Wikibase\Entity;

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
 * @group ApiCreateClaimTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiCreateClaimTest extends \ApiTestCase {

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

	public function testValidRequest() {
		/**
		 * @var Entity $entity
		 * @var Entity $property
		 */
		list( $entity, $property ) = self::getNewEntityAndProperty();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $entity->getPrefixedId(),
			'snaktype' => 'value',
			'property' => $property->getPrefixedId(),
			'value' => 'foo',
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );
		$this->assertArrayHasKey( 'lastrevid', $resultArray['claim'], 'claim has a lastrevid key' );

		$claim = $resultArray['claim'];

		foreach ( array( 'id', 'mainsnak', 'type', 'rank' ) as $requiredKey ) {
			$this->assertArrayHasKey( $requiredKey, $claim, 'claim has a "' . $requiredKey . '" key' );
		}

		$entityId = \Wikibase\Entity::getIdFromClaimGuid( $claim['id'] );

		$this->assertEquals( $entity->getPrefixedId(), $entityId );

		$this->assertEquals( 'value', $claim['mainsnak']['snaktype'] );

		$entityContent = \Wikibase\EntityContentFactory::singleton()->getFromId( $entity->getId() );

		$this->assertTrue( $entityContent->getEntity()->hasClaimWithGuid( $claim['id'] ) );
	}

	public function invalidRequestProvider() {
		$argLists = array();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => 'q0',
			'snaktype' => 'value',
			'property' => '-',
			'value' => 'foo',
		);

		$argLists[] = array( 'cant-load-entity-content', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => 'q0',
			'value' => 'foo',
		);

		$argLists[] = array( 'unknownerror', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'hax',
			'property' => '-',
			'value' => 'foo',
		);

		$argLists[] = array( 'unknown_snaktype', $params );

		foreach ( array( 'entity', 'snaktype' ) as $requiredParam ) {
			$params = array(
				'action' => 'wbcreateclaim',
				'entity' => '-',
				'snaktype' => 'value',
				'property' => '-',
				'value' => 'foo',
			);

			unset( $params[$requiredParam] );

			$argLists[] = array( 'no' . $requiredParam, $params );
		}

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'value' => 'foo',
		);

		$argLists[] = array( 'claim-property-id-missing', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => '-',
			'snaktype' => 'value',
			'property' => '-',
		);

		$argLists[] = array( 'claim-value-missing', $params );

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
			$params['entity'] = $entity->getPrefixedId();
		}

		if ( array_key_exists( 'property', $params ) && $params['property'] === '-' ) {
			$params['property'] = $property->getPrefixedId();
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

}
