<?php

namespace Wikibase\Test;

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
 * @ingroup WikibaseTest
 *
 * @group API
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

	public function testValidRequest() {
		$entity = \Wikibase\ItemObject::newEmpty();
		$content = new \Wikibase\ItemContent( $entity );
		$content->save( '', null, EDIT_NEW );
		$entity = $content->getEntity();

		$dataTypes = \Wikibase\Settings::get( 'dataTypes' );
		$property = \Wikibase\PropertyObject::newFromType( reset( $dataTypes ) );
		$content = new \Wikibase\PropertyContent( $property );
		$content->save( '', null, EDIT_NEW );
		$property = $content->getEntity();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $entity->getPrefixedId(),
			'snaktype' => 'somevalue',
			'property' => $property->getPrefixedId(),
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );

		$claim = $resultArray['claim'];

		foreach ( array( 'id', 'mainsnak', 'type', 'rank' ) as $requiredKey ) {
			$this->assertArrayHasKey( $requiredKey, $claim, 'claim has a "' . $requiredKey . '" key' );
		}

		$entityId = \Wikibase\EntityObject::getIdFromClaimGuid( $claim['id'] );

		$this->assertEquals( $entity->getPrefixedId(), $entityId );

		$this->assertEquals( 'somevalue', $claim['mainsnak']['snaktype'] );
	}

	public function invalidRequestProvider() {
		$entity = \Wikibase\ItemObject::newEmpty();
		$content = new \Wikibase\ItemContent( $entity );
		$content->save( '', null, EDIT_NEW );
		$entity = $content->getEntity();

		$dataTypes = \Wikibase\Settings::get( 'dataTypes' );
		$property = \Wikibase\PropertyObject::newFromType( reset( $dataTypes ) );
		$content = new \Wikibase\PropertyContent( $property );
		$content->save( '', null, EDIT_NEW );
		$property = $content->getEntity();

		$argLists = array();

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => 'q0',
			'snaktype' => 'value',
			'property' => $property->getPrefixedId(),
			'value' => 'foo',
		);

		$argLists[] = array( 'cant-load-entity-content', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $entity->getPrefixedId(),
			'snaktype' => 'value',
			'property' => 'q0',
			'value' => 'foo',
		);

		$argLists[] = array( 'unknownerror', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $entity->getPrefixedId(),
			'snaktype' => 'value',
			'property' => 'q0',
			'value' => 'foo',
		);

		$argLists[] = array( 'unknownerror', $params );

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $entity->getPrefixedId(),
			'snaktype' => 'hax',
			'property' => $property->getPrefixedId(),
			'value' => 'foo',
		);

		$argLists[] = array( 'unknown_snaktype', $params );

		foreach ( array( 'entity', 'snaktype', 'property' ) as $requiredParam ) {
			$params = array(
				'action' => 'wbcreateclaim',
				'entity' => $entity->getPrefixedId(),
				'snaktype' => 'value',
				'property' => $property->getPrefixedId(),
				'value' => 'foo',
			);

			unset( $params[$requiredParam] );

			$argLists[] = array( 'no' . $requiredParam, $params );
		}

		$params = array(
			'action' => 'wbcreateclaim',
			'entity' => $entity->getPrefixedId(),
			'snaktype' => 'hax',
			'property' => $property->getPrefixedId(),
		);

		$argLists[] = array( 'claim-value-missing', $params );

		return array();
		// TODO: during PHPUnit run the tests are thrown as usage exceptions
		// which makes the test think there is an error and fail.

		//return $argLists;
	}

	/**
	 * @dataProvider invalidRequestProvider
	 *
	 * @param string $errorCode
	 * @param array $params
	 */
	public function testInvalidRequest( $errorCode, array $params ) {
		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has an error key' );

		$error = $resultArray['error'];

		$this->assertArrayHasKey( 'code', $error, 'error element has a code key' );
		$this->assertArrayHasKey( 'info', $error, 'error element has an info key' );

		$this->assertEquals( $errorCode, $error['code'] );
	}

}
