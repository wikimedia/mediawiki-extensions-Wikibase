<?php

namespace Wikibase\Test\Api;

use DataValues\DataValue;
use DataValues\DataValueFactory;
use DataValues\StringValue;
use FormatJson;
use Revision;
use UsageException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Claim\Claims;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\EntityIdLinkFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;
use WikiPage;

/**
 * @covers Wikibase\Api\SetClaimValue
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group SetClaimValueTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class SetClaimValueTest extends WikibaseApiTestCase {

	/**
	 * @var ValueFormatter
	 */
	private $entityIdFormatter = null;

	/**
	 * @var ValueFormatter
	 */
	private $propertyValueFormatter = null;

	public function setUp() {
		parent::setUp();

		static $hasEntities = false;

		if ( !$hasEntities ) {
			$this->initTestEntities( array( 'StringProp', 'Berlin' ) );
			$hasEntities = true;
		}
	}

	/**
	 * @param Item $item
	 * @param PropertyId $propertyId
	 *
	 * @return Item
	 */
	private function addStatementsAndSave( Item $item, PropertyId $propertyId ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_NEW );

		$statement = new Statement( new Claim( new PropertyValueSnak( $propertyId, new StringValue( 'o_O' ) ) ) );
		$statement->setGuid( $item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P' );
		$item->addClaim( $statement );

		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );
		return $item;
	}

	/**
	 * @param EntityId $propertyId
	 *
	 * @return Entity[]
	 */
	protected function getEntities( EntityId $propertyId ) {
		$item = Item::newEmpty();

		return array(
			$this->addStatementsAndSave( $item, $propertyId ),
		);
	}

	public function testValidRequests() {
		$argLists = array();

		$property = Property::newFromType( 'commonsMedia' );

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $property, '', $GLOBALS['wgUser'], EDIT_NEW );

		foreach( $this->getEntities( $property->getId() ) as $entity ) {
			/**
			 * @var Claim $claim
			 */
			foreach ( $entity->getClaims() as $claim ) {
				$value = new StringValue( 'Kittens.png' );
				$argLists[] = array(
					'entity' => $entity,
					'claimGuid' => $claim->getGuid(),
					'value' => $value->getArrayValue(),
					'expectedSummary' => $this->getExpectedSummary( $claim, $value )
				);
			}
		}

		foreach ( $argLists as $argList ) {
			call_user_func_array( array( $this, 'doTestValidRequest' ), $argList );
		}
	}

	public function doTestValidRequest( Entity $entity, $claimGuid, $value, $expectedSummary ) {
		$entityLookup = WikibaseRepo::getDefaultInstance()->getEntityLookup();
		$obtainedEntity = $entityLookup->getEntity( $entity->getId() );
		$claimCount = count( $obtainedEntity->getClaims() );

		$params = array(
			'action' => 'wbsetclaimvalue',
			'claim' => $claimGuid,
			'value' => FormatJson::encode( $value ),
			'snaktype' => 'value',
		);

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );

		$claim = $resultArray['claim'];

		$this->assertEquals( $value, $claim['mainsnak']['datavalue']['value'] );

		$obtainedEntity = $entityLookup->getEntity( $entity->getId() );

		$page = new WikiPage( WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()->getTitleForId( $entity->getId() ) );
		$generatedSummary = $page->getRevision()->getComment( Revision::RAW );
		$this->assertEquals( $expectedSummary, $generatedSummary, 'Summary mismatch' );

		$claims = new Claims( $obtainedEntity->getClaims() );

		$this->assertEquals( $claimCount, $claims->count(), 'Claim count should not change after doing a setclaimvalue request' );

		$this->assertTrue( $claims->hasClaimWithGuid( $claimGuid ) );

		$dataValue = DataValueFactory::singleton()->newFromArray( $claim['mainsnak']['datavalue'] );

		$this->assertTrue( $claims->getClaimWithGuid( $claimGuid )->getMainSnak()->getDataValue()->equals( $dataValue ) );
	}

	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testInvalidRequest( $itemHandle, $claimGuid, $snakType, $value, $error ) {
		$itemId = new ItemId( EntityTestHelper::getId( $itemHandle ) );
		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $itemId );

		if ( $claimGuid === null ) {
			$claims = $item->getClaims();

			/* @var Claim $claim */
			$claim = reset( $claims );
			$claimGuid = $claim->getGuid();
		}

		if ( !is_string( $value ) ) {
			$value = json_encode( $value );
		}

		$params = array(
			'action' => 'wbsetclaimvalue',
			'claim' => $claimGuid,
			'snaktype' => $snakType,
			'value' => $value,
		);

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request did not raise an error' );
		} catch ( UsageException $e ) {
			$this->assertEquals( $error, $e->getCodeString(),  'Invalid claim guid raised correct error' );
		}
	}

	public function invalidRequestProvider() {
		return array(
			'bad guid 1' => array( 'Berlin', 'xyz', 'value', 'abc', 'invalid-guid' ),
			'bad guid 2' => array( 'Berlin', 'x$y$z', 'value', 'abc', 'invalid-guid' ),
			'bad guid 3' => array( 'Berlin', 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f', 'value', 'abc', 'invalid-guid' ),
			'bad snak type' => array( 'Berlin', null, 'alksdjf', 'abc', 'unknown_snaktype' ),
			'bad snak value' => array( 'Berlin', null, 'value', '    ', 'invalid-snak' ),
		);
	}

	private function getExpectedSummary( Claim $oldClaim, DataValue $value = null ) {
		$oldSnak = $oldClaim->getMainSnak();
		$property = $this->getEntityIdFormatter()->format( $oldSnak->getPropertyId() );

		//NOTE: new snak is always a PropertyValueSnak

		if ( $value === null ) {
			$value = $oldSnak->getDataValue();
		}

		$value = $this->getPropertyValueFormatter()->format( $value );
		return '/* wbsetclaimvalue:1| */ ' . $property . ': ' . $value;
	}

	/**
	 * Returns an EntityIdFormatter like the one that should be used internally for generating
	 * summaries.
	 *
	 * @return ValueFormatter
	 */
	protected function getEntityIdFormatter() {
		if ( !$this->entityIdFormatter ) {
			$options = new FormatterOptions();

			$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
			$this->entityIdFormatter = new EntityIdLinkFormatter( $options, $titleLookup );
		}

		return $this->entityIdFormatter;
	}

	/**
	 * Returns a ValueFormatter like the one that should be used internally for generating
	 * summaries.
	 *
	 * @return ValueFormatter
	 */
	protected function getPropertyValueFormatter() {
		if ( !$this->propertyValueFormatter ) {
			$idFormatter = $this->getEntityIdFormatter();

			$options = new FormatterOptions();
			$options->setOption( 'formatter-builders-text/plain', array(
				'VT:wikibase-entityid' => function() use ( $idFormatter ) {
					return $idFormatter;
				}
			) );

			$factory = WikibaseRepo::getDefaultInstance()->getValueFormatterFactory();
			$this->propertyValueFormatter = $factory->getValueFormatter( SnakFormatter::FORMAT_PLAIN, $options );
		}

		return $this->propertyValueFormatter;
	}

}
