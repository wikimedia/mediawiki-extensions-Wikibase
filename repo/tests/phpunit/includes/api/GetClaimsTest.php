<?php

namespace Wikibase\Test\Api;

use DataValues\StringValue;
use UsageException;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\GetClaims
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group GetClaimsTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adam Shorland
 */
class GetClaimsTest extends \ApiTestCase {

	/**
	 * @param Entity $entity
	 * @param int $flags
	 */
	private function save( Entity $entity, $flags = null ) {
		if ( $flags === null ) {
			$flags = $entity->getId() ? EDIT_UPDATE : EDIT_NEW;
		}

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$rev = $store->saveEntity( $entity, '', $GLOBALS['wgUser'], $flags );

		$entity->setId( $rev->getEntity()->getId() );
	}

	/**
	 * @param Item $item
	 */
	private function addStatements( Item $item, PropertyId $property ) {
		if ( !$item->getId() ) {
			$this->save( $item );
		}

		/** @var $statements Statement[] */
		$statements[0] = $item->newClaim( new PropertyNoValueSnak( $property ) );
		$statements[1] = $item->newClaim( new PropertyNoValueSnak( $property ) );
		$statements[2] = $item->newClaim( new PropertySomeValueSnak( $property ) );
		$statements[3] = $item->newClaim( new PropertyValueSnak( $property, new StringValue( 'o_O' ) ) );

		foreach ( $statements as $key => $statement ) {
			$statement->setGuid( $item->getId()->getPrefixedId() . '$D8404CDA-56A1-4334-AF13-A3290BCD9CL' . $key );
			$item->addClaim( $statement );
		}
	}

	/**
	 * @return Entity[]
	 */
	private function getNewEntities() {
		$property = Property::newFromType( 'string' );
		$this->save( $property );

		$propertyId = $property->getId();

		$item = Item::newEmpty();
		$this->addStatements( $item, $propertyId );
		$this->save( $item );

		return array(
			$property,
			$item,
		);
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	private function getDataTypeLookup() {
		$lookup = $this->getMock( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup' );

		$lookup->expects( $this->any() )
			->method( 'getDataTypeIdForProperty' )
			->will( $this->returnValue( 'string' ) );

		return $lookup;
	}

	public function validRequestProvider() {
		$entities = $this->getNewEntities();

		$argLists = array();

		foreach ( $entities as $entity ) {
			$params = array(
				'action' => 'wbgetclaims',
				'entity' => $entity->getId()->getSerialization(),
			);

			$argLists[] = array( $params, $entity->getClaims(), true );

			/**
			 * @var Claim $claim
			 */
			foreach ( $entity->getClaims() as $claim ) {
				$params = array(
					'action' => 'wbgetclaims',
					'claim' => $claim->getGuid(),
				);
				$argLists[] = array( $params, array( $claim ), true );

				$params['ungroupedlist'] = true;
				$argLists[] = array( $params, array( $claim ), false );
			}

			foreach ( array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED ) as $rank ) {
				$params = array(
					'action' => 'wbgetclaims',
					'entity' => $entity->getId()->getSerialization(),
					'rank' => ClaimSerializer::serializeRank( $rank ),
				);

				$claims = array();

				foreach ( $entity->getClaims() as $claim ) {
					if ( $claim instanceof Statement && $claim->getRank() === $rank ) {
						$claims[] = $claim;
					}
				}

				$argLists[] = array( $params, $claims, true );
			}
		}

		return $argLists;
	}

	public function testValidRequests() {
		foreach ( $this->validRequestProvider() as $argList ) {
			list( $params, $claims, $groupedByProperty ) = $argList;

			$this->doTestValidRequest( $params, $claims, $groupedByProperty );
		}
	}

	/**
	 * @param string[] $params
	 * @param Claims|Claim[] $claims
	 * @param bool $groupedByProperty
	 */
	public function doTestValidRequest( array $params, $claims, $groupedByProperty ) {
		if ( is_array( $claims ) ) {
			$claims = new Claims( $claims );
		}
		$options = new SerializationOptions();
		if( !$groupedByProperty ) {
			$options->setOption( SerializationOptions::OPT_GROUP_BY_PROPERTIES, array() );
		}

		$serializerFactory = new SerializerFactory( null, $this->getDataTypeLookup() );
		$serializer = $serializerFactory->newSerializerForObject( $claims );
		$serializer->setOptions( $options );
		$expected = $serializer->getSerialized( $claims );

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claims', $resultArray, 'top level element has a claims key' );

		$this->assertEquals( $expected, $resultArray['claims'] );
	}

	/**
	 * @dataProvider invalidClaimProvider
	 */
	public function testGetInvalidClaims( $claimGuid ) {
		$params = array(
			'action' => 'wbgetclaims',
			'claim' => $claimGuid
		);

		try {
			$this->doApiRequest( $params );
			$this->fail( 'Invalid claim guid did not throw an error' );
		} catch ( UsageException $e ) {
			$this->assertEquals( 'invalid-guid', $e->getCodeString(), 'Invalid claim guid raised correct error' );
		}
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ),
			array( 'x$y$z' )
		);
	}

	/**
	 * @dataProvider getInvalidIdsProvider
	 */
	public function testGetInvalidIds( $entity, $property ) {
		if ( !$entity ) {
			$item = Item::newEmpty();
			$this->addStatements( $item, new PropertyId( 'P13' ) );

			$this->save( $item );
			$entity = $item->getId()->getSerialization();
		}

		$params = array(
			'action' => 'wbgetclaims',
			'entity' => $entity,
			'property' => $property,
		);

		try {
			$this->doApiRequest( $params );
			$this->fail( 'Invalid entity id did not throw an error' );
		} catch ( UsageException $e ) {
			$this->assertEquals( 'param-invalid', $e->getCodeString(), 'Invalid entity id raised correct error' );
		}
	}

	public function getInvalidIdsProvider() {
		return array(
			array( null, 'nopeNopeNope' ),
			array( 'whatTheFuck', 'P42' ),
		);
	}

}
