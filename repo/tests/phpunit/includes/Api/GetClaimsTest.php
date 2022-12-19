<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use ApiUsageException;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Repo\StatementRankSerializer;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\GetClaims
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Addshore
 */
class GetClaimsTest extends ApiTestCase {

	/**
	 * @var SerializerFactory
	 */
	private $serializerFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->serializerFactory = new SerializerFactory(
			new DataValueSerializer(),
			SerializerFactory::OPTION_DEFAULT
		);
	}

	private function save( EntityDocument $entity ) {
		$flags = $entity->getId() ? EDIT_UPDATE : EDIT_NEW;

		$store = WikibaseRepo::getEntityStore();

		$rev = $store->saveEntity( $entity, '', $this->getTestUser()->getUser(), $flags );

		$entity->setId( $rev->getEntity()->getId() );
	}

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

		return [
			$property,
			$item,
		];
	}

	public function validRequestProvider() {
		$entities = $this->getNewEntities();

		$argLists = [];

		foreach ( $entities as $entity ) {
			$idSerialization = $entity->getId()->getSerialization();
			/** @var StatementListProvider $entity */
			$statements = $entity->getStatements();

			$params = [
				'action' => 'wbgetclaims',
				'entity' => $idSerialization,
			];

			$argLists[] = [ $params, $statements->toArray() ];

			foreach ( $statements->toArray() as $statement ) {
				$params = [
					'action' => 'wbgetclaims',
					'claim' => $statement->getGuid(),
				];
				$argLists[] = [ $params, [ $statement ] ];
			}

			foreach ( [ Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED ] as $rank ) {
				$statementRankSerializer = new StatementRankSerializer();
				$params = [
					'action' => 'wbgetclaims',
					'entity' => $idSerialization,
					'rank' => $statementRankSerializer->serialize( $rank ),
				];

				$statementsByRank = $statements->getByRank( $rank )->toArray();
				$argLists[] = [ $params, $statementsByRank ];
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
		$statements = new StatementList( ...$statements );

		$serializer = $this->serializerFactory->newStatementListSerializer();
		$expected = $serializer->serialize( $statements );

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertIsArray( $resultArray, 'top level element is an array' );
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
		$params = [
			'action' => 'wbgetclaims',
			'claim' => $guid,
		];

		try {
			$this->doApiRequest( $params );
			$this->fail( 'Invalid claim guid did not throw an error' );
		} catch ( ApiUsageException $e ) {
			$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
			$this->assertEquals( 'invalid-guid', $msg->getApiCode(), 'Invalid claim guid raised correct error' );
		}
	}

	public function invalidClaimProvider() {
		return [
			[ 'xyz' ],
			[ 'x$y$z' ],
		];
	}

	/**
	 * @dataProvider getInvalidIdsProvider
	 */
	public function testGetInvalidIds( $entity, $property ) {
		if ( !$entity ) {
			$item = new Item();
			$this->addStatements( $item, new NumericPropertyId( 'P13' ) );

			$this->save( $item );
			$entity = $item->getId()->getSerialization();
		}

		$params = [
			'action' => 'wbgetclaims',
			'entity' => $entity,
			'property' => $property,
		];

		try {
			$this->doApiRequest( $params );
			$this->fail( 'Invalid entity id did not throw an error' );
		} catch ( ApiUsageException $e ) {
			$msg = TestingAccessWrapper::newFromObject( $e )->getApiMessage();
			$this->assertEquals( 'param-invalid', $msg->getApiCode(), 'Invalid entity id raised correct error' );
		}
	}

	public function getInvalidIdsProvider() {
		return [
			[ null, 'nopeNopeNope' ],
			[ 'whatTheFuck', 'P42' ],
		];
	}

}
