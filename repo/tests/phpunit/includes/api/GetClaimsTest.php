<?php

namespace Wikibase\Test\Api;
use Wikibase\Entity;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Statement;

/**
 * Unit tests for the Wikibase\ApiGetClaims class.
 *
 * @since 0.3
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
 */
class GetClaimsTest extends \ApiTestCase {

	/**
	 * @param Entity $entity
	 *
	 * @return Entity
	 */
	protected function addClaimsAndSave( Entity $entity ) {
		wfSuppressWarnings(); // We are referencing properties that don't exist. Not relevant here.
		$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->newFromEntity( $entity );
		$content->save( '', null, EDIT_NEW );

		/** @var $claims Claim[] */
		$claims[0] = $entity->newClaimBase( new \Wikibase\PropertyNoValueSnak( 42 ) );
		$claims[1] = $entity->newClaimBase( new \Wikibase\PropertyNoValueSnak( 1 ) );
		$claims[2] = $entity->newClaimBase( new \Wikibase\PropertySomeValueSnak( 42 ) );
		$claims[3] = $entity->newClaimBase( new \Wikibase\PropertyValueSnak( 9001, new \DataValues\StringValue( 'o_O' ) ) );

		foreach( $claims as $key => $claim ){
			$claim->setGuid( $entity->getId()->getPrefixedId() . '$D8404CDA-56A1-4334-AF13-A3290BCD9CL' . $key );
			$entity->addClaim( $claim );
		}

		$content->save( '' );
		wfRestoreWarnings();

		return $content->getEntity();
	}

	/**
	 * @return Entity[]
	 */
	protected function getNewEntities() {
		$property = \Wikibase\Property::newEmpty();

		$property->setDataTypeId( 'string' );

		return array(
			$this->addClaimsAndSave( \Wikibase\Item::newEmpty() ),
			$this->addClaimsAndSave( $property ),
		);
	}

	public function validRequestProvider() {
		$entities = $this->getNewEntities();

		$argLists = array();

		foreach ( $entities as $entity ) {
			$params = array(
				'action' => 'wbgetclaims',
				'entity' => $this->getFormattedIdForEntity( $entity ),
			);

			$argLists[] = array( $params, $entity->getClaims() );

			/**
			 * @var Claim $claim
			 */
			foreach ( $entity->getClaims() as $claim ) {
				$params = array(
					'action' => 'wbgetclaims',
					'claim' => $claim->getGuid(),
				);

				$argLists[] = array( $params, array( $claim ) );
			}

			foreach ( array( Statement::RANK_DEPRECATED, Statement::RANK_NORMAL, Statement::RANK_PREFERRED ) as $rank ) {
				$params = array(
					'action' => 'wbgetclaims',
					'entity' => $this->getFormattedIdForEntity( $entity ),
					'rank' => \Wikibase\Lib\Serializers\ClaimSerializer::serializeRank( $rank ),
				);

				$claims = array();

				foreach ( $entity->getClaims() as $claim ) {
					if ( $claim instanceof Statement && $claim->getRank() === $rank ) {
						$claims[] = $claim;
					}
				}

				$argLists[] = array( $params, $claims );
			}
		}

		return $argLists;
	}

	protected function getFormattedIdForEntity( Entity $entity ) {
		$idFormatter = WikibaseRepo::getDefaultInstance()->getIdFormatter();
		return $idFormatter->format( $entity->getId() );
	}

	public function testValidRequests() {
		foreach ( $this->validRequestProvider() as $argList ) {
			list( $params, $claims ) = $argList;

			$this->doTestValidRequest( $params, $claims );
		}
	}

	/**
	 * @param string[] $params
	 * @param Claims|Claim[] $claims
	 */
	public function doTestValidRequest( array $params, $claims ) {
		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claims', $resultArray, 'top level element has a claims key' );

		if ( is_array( $claims ) ) {
			$claims = new \Wikibase\Claims( $claims );
		}

		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $claims );
		$expected = $serializer->getSerialized( $claims );

		$byPropClaims = new \Wikibase\ByPropertyIdArray( $claims );
		$byPropClaims->buildIndex();

		// TODO: this is a rather simplistic test.
		// Would be nicer if we could deserialize the list and then use the equals method
		// or to serialize the expected value and have a recursive array compare on that
		foreach ( $expected as $propertyId => $claimsForProperty ) {
			$id = \Wikibase\EntityId::newFromPrefixedId( $propertyId );
			$this->assertEquals(
				count( $claimsForProperty ),
				count( $byPropClaims->getByPropertyId( $id->getNumericId() ) )
			);
		}
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
		} catch ( \UsageException $e ) {
			$this->assertEquals( 'invalid-guid', $e->getCodeString(), 'Invalid claim guid raised correct error' );
		}
	}

	public function invalidClaimProvider() {
		return array(
			array( 'xyz' ),
			array( 'x$y$z' )
		);
	}
}
