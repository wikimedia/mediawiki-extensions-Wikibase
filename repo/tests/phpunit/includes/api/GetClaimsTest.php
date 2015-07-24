<?php

namespace Wikibase\Test\Repo\Api;

use ApiTestCase;
use DataValues\StringValue;
use UsageException;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Serializers\ClaimSerializer;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Serializers\LibSerializerFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\GetClaims
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
class GetClaimsTest extends ApiTestCase {

	/**
	 * @param EntityDocument $entity
	 */
	private function save( EntityDocument $entity ) {
		$flags = $entity->getId() ? EDIT_UPDATE : EDIT_NEW;

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();

		$rev = $store->saveEntity( $entity, '', $GLOBALS['wgUser'], $flags );

		$entity->setId( $rev->getEntity()->getId() );
	}

	/**
	 * @param Item $item
	 * @param PropertyId $propertyId
	 */
	private function addStatements( Item $item, PropertyId $propertyId ) {
		if ( !$item->getId() ) {
			$this->save( $item );
		}

		/** @var $statements Statement[] */
		$statements[0] = new Statement( new PropertyNoValueSnak( $propertyId ) );
		$statements[1] = new Statement( new PropertyNoValueSnak( $propertyId ) );
		$statements[2] = new Statement( new PropertySomeValueSnak( $propertyId ) );
		$statements[3] = new Statement( new PropertyValueSnak( $propertyId, new StringValue( 'o_O' ) ) );

		foreach ( $statements as $key => $statement ) {
			$statement->setGuid( $item->getId()->getSerialization() . '$D8404CDA-56A1-4334-AF13-A3290BCD9CL' . $key );
			$item->getStatements()->addStatement( $statement );
		}
	}

	/**
	 * @return EntityDocument[]
	 */
	private function getNewEntities() {
		$property = Property::newFromType( 'string' );
		$this->save( $property );

		$propertyId = $property->getId();

		$item = new Item();
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

	/**
	 * @return array( $params, $statements, $groupedByProperty )
	 */
	public function validRequestProvider() {
		$entities = $this->getNewEntities();

		$argLists = array();

		foreach ( $entities as $entity ) {
			$idSerialization = $entity->getId()->getSerialization();
			/** @var StatementListProvider $entity */
			$statements = $entity->getStatements();

			$params = array(
				'action' => 'wbgetclaims',
				'entity' => $idSerialization,
			);

			$argLists[] = array( $params, $statements->toArray(), true );

			foreach ( $statements->toArray() as $statement ) {
				$params = array(
					'action' => 'wbgetclaims',
					'claim' => $statement->getGuid(),
				);
				$argLists[] = array( $params, array( $statement ), true );

				$params['ungroupedlist'] = true;
				$argLists[] = array( $params, array( $statement ), false );
			}

			foreach ( array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED ) as $rank ) {
				$params = array(
					'action' => 'wbgetclaims',
					'entity' => $idSerialization,
					'rank' => ClaimSerializer::serializeRank( $rank ),
				);

				$statementsByRank = $statements->getByRank( $rank )->toArray();
				$argLists[] = array( $params, $statementsByRank, true );
			}
		}

		return $argLists;
	}

	public function testValidRequests() {
		foreach ( $this->validRequestProvider() as $argList ) {
			list( $params, $statements, $groupedByProperty ) = $argList;

			$this->doTestValidRequest( $params, $statements, $groupedByProperty );
		}
	}

	/**
	 * @param string[] $params
	 * @param Statement[] $statements
	 * @param bool $groupedByProperty
	 */
	public function doTestValidRequest( array $params, array $statements, $groupedByProperty ) {
		$claims = new Claims( $statements );
		$options = new SerializationOptions();
		if ( !$groupedByProperty ) {
			$options->setOption( SerializationOptions::OPT_GROUP_BY_PROPERTIES, array() );
		}

		$serializerFactory = new LibSerializerFactory( null, $this->getDataTypeLookup() );
		$serializer = $serializerFactory->newClaimsSerializer( $options );
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
	public function testGetInvalidClaims( $guid ) {
		$params = array(
			'action' => 'wbgetclaims',
			'claim' => $guid
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
			$item = new Item();
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
