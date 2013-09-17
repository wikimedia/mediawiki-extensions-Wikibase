<?php

namespace Wikibase\Test\Api;
use Wikibase\Claim;
use Wikibase\Claims;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\Lib\Serializers\SerializerFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Statement;
use Wikibase\Reference;
use Wikibase\Snak;
use Wikibase\SnakList;
use Wikibase\PropertyValueSnak;
use Wikibase\PropertyNoValueSnak;
use Wikibase\PropertySomeValueSnak;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Lib\ClaimGuidGenerator;


/**
 * Unit tests for the Wikibase\Repo\Api\ApSetClaim class.
 *
 * @since 0.4
 *
 * @ingroup WikibaseRepoTest
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetClaimTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class SetClaimTest extends WikibaseApiTestCase {

	/**
	 * @return Snak[]
	 */
	protected function snakProvider() {
		static $hasProperties = false;

		$prop42 = new PropertyId( 'P42' );
		$prop9001 = new PropertyId( 'P9001' );
		$prop7201010 = new PropertyId( 'P7201010' );

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

		$snaks[] = new PropertyNoValueSnak( $prop42 );
		$snaks[] = new PropertySomeValueSnak( $prop9001 );
		$snaks[] = new PropertyValueSnak( $prop7201010, new \DataValues\StringValue( 'o_O' ) );

		return $snaks;
	}

	/**
	 * @return Claim[]
	 */
	protected function claimProvider() {
		$statements = array();

		$snaks = $this->snakProvider();
		$mainSnak = $snaks[0];
		$statement = new Statement( $mainSnak );
		$statements[] = $statement;

		foreach ( $snaks as $snak ) {
			$statement = clone $statement;
			$snaks = new SnakList( array( $snak ) );
			$statement->getReferences()->addReference( new Reference( $snaks ) );
			$statements[] = $statement;
		}

		$statement = clone $statement;
		$snaks = new SnakList( $this->snakProvider() );
		$statement->getReferences()->addReference( new Reference( $snaks ) );
		$statements[] = $statement;

		$statement = clone $statement;
		$snaks = new SnakList( $this->snakProvider() );
		$statement->setQualifiers( $snaks );
		$statement->getReferences()->addReference( new Reference( $snaks ) );
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

	public function testAddClaim() {
		foreach ( $this->claimProvider() as $claim ) {
			$item = Item::newEmpty();
			$content = new ItemContent( $item );
			$content->save( '', null, EDIT_NEW );

			$guidGenerator = new ClaimGuidGenerator( $item->getId() );
			$guid = $guidGenerator->newGuid();

			$claim->setGuid( $guid );

			// Addition request
			$this->makeRequest( $claim, $item->getId(), 1 );

			// Reorder qualifiers:
			if( count( $claim->getQualifiers() ) > 0 ) {
				// Simply reorder the qualifiers by putting the first qualifier to the end. This is
				// supposed to be done in the serialized representation since changing the actual
				// object might apply intrinsic sorting.
				$serializerFactory = new SerializerFactory();
				$serializer = $serializerFactory->newSerializerForObject( $claim );
				$serializedClaim = $serializer->getSerialized( $claim );
				$firstPropertyId = array_shift( $serializedClaim['qualifiers-order'] );
				array_push( $serializedClaim['qualifiers-order'], $firstPropertyId );
				$this->makeRequest( $serializedClaim, $item->getId(), 1 );
			}

			$claim = new Statement( new PropertyNoValueSnak( 9001 ) );
			$claim->setGuid( $guid );

			// Update request
			$this->makeRequest( $claim, $item->getId(), 1 );
		}
	}

	/**
	 * @param Claim|array $claim Native or serialized claim object.
	 * @param EntityId $entityId
	 * @param $claimCount
	 */
	protected function makeRequest( $claim, EntityId $entityId, $claimCount ) {
		$serializerFactory = new SerializerFactory();

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

		$content = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getFromId( $entityId );

		$this->assertInstanceOf( '\Wikibase\EntityContent', $content );

		$claims = new Claims( $content->getEntity()->getClaims() );

		$this->assertTrue( $claims->hasClaim( $claim ) );

		$savedClaim = $claims->getClaimWithGuid( $claim->getGuid() );
		if( count( $claim->getQualifiers() ) ) {
			$this->assertArrayEquals( $claim->getQualifiers()->toArray(), $savedClaim->getQualifiers()->toArray(), true );
		}

		$this->assertEquals( $claimCount, $claims->count() );
	}

	protected function makeValidRequest( array $params ) {
		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'pageinfo', $resultArray, 'top level element has a pageinfo key' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a statement key' );

		if( isset( $resultArray['claim']['qualifiers'] ) ) {
			$this->assertArrayHasKey( 'qualifiers-order', $resultArray['claim'], '"qualifiers-order" key is set when returning qualifiers' );
		}

		return $resultArray;
	}

}
