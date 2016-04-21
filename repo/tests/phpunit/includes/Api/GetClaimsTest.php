<?php

namespace Wikibase\Test\Repo\Api;

use ApiTestCase;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use UsageException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\StatementRankSerializer;

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
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Addshore
 */
class GetClaimsTest extends ApiTestCase {

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	protected function setUp() {
		parent::setUp();

		$this->serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_SERIALIZE_REFERENCE_SNAKS_WITHOUT_HASH +
			SerializerFactory::OPTION_SERIALIZE_MAIN_SNAKS_WITHOUT_HASH
		);
	}

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

		/** @var Statement[] $statements */
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
	 * @return array( $params, $statements, $groupedByProperty )
	 */
	public function validRequestProvider() {
		$entities = $this->getNewEntities();

		$argLists = [];

		foreach ( $entities as $entity ) {
			$idSerialization = $entity->getId()->getSerialization();
			/** @var StatementListProvider $entity */
			$statements = $entity->getStatements();

			$params = array(
				'action' => 'wbgetclaims',
				'entity' => $idSerialization,
			);

			$argLists[] = array( $params, $statements->toArray() );

			foreach ( $statements->toArray() as $statement ) {
				$params = array(
					'action' => 'wbgetclaims',
					'claim' => $statement->getGuid(),
				);
				$argLists[] = array( $params, array( $statement ) );
			}

			foreach ( array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED ) as $rank ) {
				$statementRankSerializer = new StatementRankSerializer();
				$params = array(
					'action' => 'wbgetclaims',
					'entity' => $idSerialization,
					'rank' => $statementRankSerializer->serialize( $rank ),
				);

				$statementsByRank = $statements->getByRank( $rank )->toArray();
				$argLists[] = array( $params, $statementsByRank );
			}
		}

		return $argLists;
	}

	public function testValidRequests() {
		foreach ( $this->validRequestProvider() as $argList ) {
			list( $params, $statements ) = $argList;

			$this->doTestValidRequest( $params, $statements );
		}
	}

	/**
	 * @param string[] $params
	 * @param Statement[] $statements
	 */
	public function doTestValidRequest( array $params, array $statements ) {
		$statements = new StatementList( $statements );

		$serializer = $this->serializerFactory->newStatementListSerializer();
		$expected = $serializer->serialize( $statements );

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claims', $resultArray, 'top level element has a claims key' );

		// Assert that value mainsnaks have a datatype added
		foreach ( $resultArray['claims'] as &$claimsByProperty ) {
			foreach ( $claimsByProperty as &$claimArray ) {
				$this->assertArrayHasKey( 'datatype', $claimArray['mainsnak'] );
				unset( $claimArray['mainsnak']['datatype'] );
			}
		}

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
