<?php

namespace Wikibase\Test\Api;
use Wikibase\Claim;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyContent;

/**
 * Unit tests for the Wikibase\Repo\Api\ApSetClaim class.
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
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group ApSetClaimTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SetClaimTest extends WikibaseApiTestCase {

	/**
	 * @return \Wikibase\Snak[]
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

		$snaks = $this->snakProvider();
		$mainSnak = $snaks[0];
		$statement = new \Wikibase\Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $snaks as $snak ) {
			$statement = clone $statement;
			$snaks = new \Wikibase\SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new \Wikibase\SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
		$statements[] = $statement;

		$statement = clone $statement;
		$snaks = new \Wikibase\SnakList( $this->snakProvider() );
		$statement->setQualifiers( $snaks );
		$statement->getReferences()->addReference( new \Wikibase\Reference( $snaks ) );
		$statements[] = $statement;

		$ranks = array(
			\Wikibase\Statement::RANK_DEPRECATED,
			\Wikibase\Statement::RANK_NORMAL,
			\Wikibase\Statement::RANK_PREFERRED
		);

		/**
		 * @var \Wikibase\Statement[] $statements
		 */
		foreach ( $statements as &$statement ) {
			$statement->setRank( $ranks[array_rand( $ranks )] );
		}

		return $statements;
	}

	public function testAddClaim() {
		foreach ( $this->claimProvider() as $claim ) {
			$item = \Wikibase\Item::newEmpty();
			$content = new \Wikibase\ItemContent( $item );
			$content->save( '', null, EDIT_NEW );

			$guidGenerator = new \Wikibase\Lib\ClaimGuidGenerator( $item->getId() );
			$guid = $guidGenerator->newGuid();

			$claim->setGuid( $guid );

			// Addition request
			$this->makeRequest( $claim, $item->getId(), 1 );

			// Reorder qualifiers:
			if( count( $claim->getQualifiers() ) > 0 ) {
				// Simply reorder the qualifiers by putting the first qualifier to the end. This is
				// supposed to be done in the serialized representation since changing the actual
				// object might apply intrinsic sorting.
				$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
				$serializer = $serializerFactory->newSerializerForObject( $claim );
				$serializedClaim = $serializer->getSerialized( $claim );
				$firstPropertyId = array_shift( $serializedClaim['qualifiers']['order'] );
				array_push( $serializedClaim['qualifiers']['order'], $firstPropertyId );
				$this->makeRequest( $serializedClaim, $item->getId(), 1 );
			}

			$claim = new \Wikibase\Statement( new \Wikibase\PropertyNoValueSnak( 9001 ) );
			$claim->setGuid( $guid );

			// Update request
			$this->makeRequest( $claim, $item->getId(), 1 );
		}
	}

	/**
	 * @param \Wikibase\Claim|array $claim Native or serialized claim object.
	 * @param EntityId $entityId
	 * @param $claimCount
	 */
	protected function makeRequest( $claim, \Wikibase\EntityId $entityId, $claimCount ) {
		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();

		if( is_a( $claim, '\Wikibase\Claim' ) ) {
			$serializer = $serializerFactory->newSerializerForObject( $claim );
			$serializedClaim = $serializer->getSerialized( $claim );
		} else {
			$serializer = $serializerFactory->newUnserializerForClass( 'Wikibase\Claim' );
			$serializedClaim = $claim;
			$claim = $serializer->newFromSerialization( $serializedClaim );
		}

		$params = array(
			'action' => 'wbsetclaim',
			'claim' => \FormatJson::encode( $serializedClaim ),
		);

		$this->makeValidRequest( $params );

		$content = \Wikibase\EntityContentFactory::singleton()->getFromId( $entityId );

		$this->assertInstanceOf( '\Wikibase\EntityContent', $content );

		$claims = new \Wikibase\Claims( $content->getEntity()->getClaims() );

		$this->assertTrue( $claims->hasClaim( $claim ) );

		$savedClaim = $claims->getClaimWithGuid( $claim->getGuid() );
		if( count( $claim->getQualifiers() ) ) {
			$this->assertArrayEquals( $claim->getQualifiers()->toArray(), $savedClaim->getQualifiers()->toArray(), true );
		}

		$this->assertEquals( $claimCount, $claims->count() );
	}

	protected function makeValidRequest( array $params ) {
		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		if( isset( $resultArray['claim']['qualifiers'] ) ) {
			$this->assertArrayHasKey( 'snaks', $resultArray['claim']['qualifiers'], '"snaks" key is set when returning qualifiers' );
			$this->assertArrayHasKey( 'order', $resultArray['claim']['qualifiers'], '"order" key is set when returning qualifiers' );
		}

		return $resultArray;
	}

}
