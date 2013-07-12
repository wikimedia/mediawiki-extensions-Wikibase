<?php

namespace Wikibase\Test\Api;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\Snak;
use Wikibase\Statement;
use Wikibase\Claim;
use Wikibase\EntityId;

/**
 * Unit tests for the Wikibase\Repo\Api\SetQualifier class.
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
 * @group SetQualifierTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class SetQualifierTest extends ModifyItemBase {

	/**
	 * @return Snak[]
	 */
	protected function snakProvider() {
		static $hasProperties = false;

		$prop42 = new EntityId( Property::ENTITY_TYPE, 42 );
		$prop9001 = new EntityId( Property::ENTITY_TYPE, 9001 );
		$prop7201010 = new EntityId( Property::ENTITY_TYPE, 7201010 );

		if ( !$hasProperties ) {
			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop42 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop9001 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop7201010 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$hasProperties = true;
		}

		$snaks = array();

		$snaks[] = new \Wikibase\PropertyNoValueSnak( $prop42 );
		$snaks[] = new \Wikibase\PropertySomeValueSnak( $prop9001 );
		$snaks[] = new \Wikibase\PropertyValueSnak( $prop7201010, new \DataValues\StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Claim[]
	 */
	protected function claimProvider() {
		$statements = array();

		$mainSnak = new \Wikibase\PropertyNoValueSnak( 42 );
		$statement = new \Wikibase\Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $this->snakProvider() as $snak ) {
			$statement = clone $statement;
			$snaks = new \Wikibase\SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new \Wikibase\SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
		$statements[] = $statement;

		$ranks = array(
			Statement::RANK_DEPRECATED,
			Statement::RANK_NORMAL,
			Statement::RANK_PREFERRED
		);

		/**
		 * @var Statement[] $statements
		 */
		foreach ( $statements as &$statement ) {
			$statement->setRank( $ranks[array_rand( $ranks )] );
		}

		return $statements;
	}

	/**
	 * @return Snak[]
	 */
	protected function newQualifierProvider() {
		$properties = array();

		$property1 = \Wikibase\Property::newFromType( 'commonsMedia' );
		$properties[] = $property1;

		$property2 = \Wikibase\Property::newFromType( 'wikibase-item' );
		$properties[] = $property2;

		foreach( $properties as $property ) {
			$content = new \Wikibase\PropertyContent( $property );
			$status = $content->save( '', null, EDIT_NEW );

			$this->assertTrue( $status->isOK() );
		}

		return array(
			new \Wikibase\PropertySomeValueSnak( 9001 ),
			new \Wikibase\PropertyNoValueSnak( 9001 ),
			new \Wikibase\PropertyValueSnak( $property1->getId(), new \DataValues\StringValue( 'Dummy.jpg' ) ),
			new \Wikibase\PropertyValueSnak( $property2->getId(), new EntityId( Item::ENTITY_TYPE, 802 ) ),
		);
	}

	public function testRequests() {
		foreach( $this->claimProvider() as $claim ) {
			$item = \Wikibase\Item::newEmpty();
			$item->setId( new EntityId( Item::ENTITY_TYPE, 802 ) );
			$content = new \Wikibase\ItemContent( $item );
			$content->save( '', null, EDIT_NEW );

			$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
			$claim->setGuid( $guidGenerator->newGuid() );
			$item->addClaim( $claim );

			$content->save( '' );

			// This qualifier should not be part of the Claim yet!
			foreach ( $this->newQualifierProvider() as $qualifier ) {
				$this->makeAddRequest( $claim->getGuid(), $qualifier, $item->getId() );
			}
		}
	}

	protected function makeAddRequest( $statementGuid, Snak $qualifier, EntityId $entityId ) {
		$token = $this->getItemToken();

		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $statementGuid,
			'snaktype' => $qualifier->getType(),
			'property' => $qualifier->getPropertyId()->getPrefixedId(),
			'token' => $token
		);

		if ( $qualifier instanceof \Wikibase\PropertyValueSnak ) {
			$dataValue = $qualifier->getDataValue();
			$params['value'] = \FormatJson::encode( $dataValue->getArrayValue() );
		}

		$this->makeValidRequest( $params );

		$content = \Wikibase\EntityContentFactory::singleton()->getFromId( $entityId );

		$this->assertInstanceOf( '\Wikibase\EntityContent', $content );

		$claims = new \Wikibase\Claims( $content->getEntity()->getClaims() );

		$this->assertTrue( $claims->hasClaimWithGuid( $params['claim'] ) );

		$claim = $claims->getClaimWithGuid( $params['claim'] );

		$this->assertTrue(
			$claim->getQualifiers()->hasSnak( $qualifier ),
			'The qualifier should exist in the qualifier list after making the request'
		);
	}

	protected function makeValidRequest( array $params ) {
		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		return $resultArray;
	}

	// TODO: test update requests


	/**
	 * @dataProvider invalidClaimProvider
	 */
	public function testInvalidClaimGuid( $claimGuid ) {
		$caughtException = false;

		$params = array(
			'action' => 'wbsetqualifier',
			'claim' => $claimGuid,
			'property' => 7,
			'snaktype' => 'value',
			'value' => 'abc',
			'token' => $GLOBALS['wgUser']->getEditToken()
		);

		try {
			$this->doApiRequest( $params );
		} catch ( \UsageException $e ) {
			$this->assertEquals( $e->getCodeString(), 'setqualifier-invalid-guid', 'Invalid claim guid raised correct error' );
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
