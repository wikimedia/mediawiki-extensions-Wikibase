<?php

namespace Wikibase\Repo\Tests\Api;

use DataValues\StringValue;
use FormatJson;
use MediaWiki\Storage\RevisionRecord;
use ApiUsageException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\EntityIdPlainLinkFormatter;
use Wikibase\Lib\EntityIdValueFormatter;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;
use WikiPage;

/**
 * @covers Wikibase\Repo\Api\SetClaimValue
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @group medium
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
class SetClaimValueTest extends WikibaseApiTestCase {

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter = null;

	/**
	 * @var ValueFormatter|null
	 */
	private $propertyValueFormatter = null;

	protected function setUp() {
		parent::setUp();

		static $hasEntities = false;

		if ( !$hasEntities ) {
			$this->initTestEntities( [ 'StringProp', 'Berlin' ] );
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

		$snak = new PropertyValueSnak( $propertyId, new StringValue( 'o_O' ) );
		$guid = $item->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P';
		$item->getStatements()->addNewStatement( $snak, null, null, $guid );

		$store->saveEntity( $item, '', $GLOBALS['wgUser'], EDIT_UPDATE );

		return $item;
	}

	/**
	 * @param PropertyId $propertyId
	 *
	 * @return Item[]
	 */
	protected function getItems( PropertyId $propertyId ) {
		$item = new Item();

		return [
			$this->addStatementsAndSave( $item, $propertyId ),
		];
	}

	public function testValidRequests() {
		$argLists = [];

		$property = Property::newFromType( 'commonsMedia' );

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $property, '', $GLOBALS['wgUser'], EDIT_NEW );

		foreach ( $this->getItems( $property->getId() ) as $item ) {
			foreach ( $item->getStatements()->toArray() as $statement ) {
				$value = new StringValue( 'Kittens.png' );
				$argLists[] = [
					'itemId' => $item->getId(),
					'guid' => $statement->getGuid(),
					'value' => $value->getArrayValue(),
					'expectedSummary' => $this->getExpectedSummary( $statement, $value )
				];
			}
		}

		foreach ( $argLists as $argList ) {
			call_user_func_array( [ $this, 'doTestValidRequest' ], $argList );
		}
	}

	public function doTestValidRequest( ItemId $itemId, $guid, $value, $expectedSummary ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityLookup = $wikibaseRepo->getEntityLookup();
		/** @var Item $obtainedEntity */
		$obtainedEntity = $entityLookup->getEntity( $itemId );
		$statementCount = $obtainedEntity->getStatements()->count();

		$params = [
			'action' => 'wbsetclaimvalue',
			'claim' => $guid,
			'value' => FormatJson::encode( $value ),
			'snaktype' => 'value',
		];

		list( $resultArray, ) = $this->doApiRequestWithToken( $params );

		$this->assertResultSuccess( $resultArray );
		$this->assertInternalType( 'array', $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );

		$claim = $resultArray['claim'];

		$this->assertEquals( $value, $claim['mainsnak']['datavalue']['value'] );

		/** @var StatementListProvider $obtainedEntity */
		$obtainedEntity = $entityLookup->getEntity( $itemId );

		$page = new WikiPage( $wikibaseRepo->getEntityTitleLookup()->getTitleForId( $itemId ) );
		$generatedSummary = $page->getRevision()->getComment( RevisionRecord::RAW );
		$this->assertEquals( $expectedSummary, $generatedSummary, 'Summary mismatch' );

		$statements = $obtainedEntity->getStatements();

		$this->assertSame(
			$statementCount,
			$statements->count(),
			'Statement count should not change after doing a setclaimvalue request'
		);

		$obtainedClaim = $statements->getFirstStatementWithGuid( $guid );

		$this->assertNotNull( $obtainedClaim );

		$dataValue = $wikibaseRepo->getDataValueFactory()->newFromArray( $claim['mainsnak']['datavalue'] );

		$this->assertTrue( $obtainedClaim->getMainSnak()->getDataValue()->equals( $dataValue ) );
	}

	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testInvalidRequest( $itemHandle, $guid, $snakType, $value, $error ) {
		$itemId = new ItemId( EntityTestHelper::getId( $itemHandle ) );
		$item = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $itemId );

		if ( $guid === null ) {
			/** @var Item $item */
			$statements = $item->getStatements()->toArray();
			/** @var Statement $statement */
			$statement = reset( $statements );
			$guid = $statement->getGuid();
		}

		if ( !is_string( $value ) ) {
			$value = json_encode( $value );
		}

		$params = [
			'action' => 'wbsetclaimvalue',
			'claim' => $guid,
			'snaktype' => $snakType,
			'value' => $value,
		];

		try {
			$this->doApiRequestWithToken( $params );
			$this->fail( 'Invalid request did not raise an error' );
		} catch ( ApiUsageException $ex ) {
			$msg = TestingAccessWrapper::newFromObject( $ex )->getApiMessage();
			$this->assertEquals( $error, $msg->getApiCode(), 'Invalid request raised correct error' );
		}
	}

	public function invalidRequestProvider() {
		return [
			'bad guid 1' => [ 'Berlin', 'xyz', 'value', 'abc', 'invalid-guid' ],
			'bad guid 2' => [ 'Berlin', 'x$y$z', 'value', 'abc', 'invalid-guid' ],
			'bad guid 3' => [ 'Berlin', 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f', 'value', 'abc', 'invalid-guid' ],
			'bad snak type' => [ 'Berlin', null, 'alksdjf', 'abc', 'unknown_snaktype' ],
			'bad snak value' => [ 'Berlin', null, 'value', '    ', 'invalid-snak' ],
		];
	}

	private function getExpectedSummary( Statement $oldStatement, StringValue $value ) {
		$oldSnak = $oldStatement->getMainSnak();
		$property = $this->getEntityIdFormatter()->formatEntityId( $oldSnak->getPropertyId() );

		$value = $this->getPropertyValueFormatter()->format( $value );
		return '/* wbsetclaimvalue:1| */ ' . $property . ': ' . $value;
	}

	/**
	 * Returns an EntityIdFormatter like the one that should be used internally for generating
	 * summaries.
	 *
	 * @return EntityIdFormatter
	 */
	protected function getEntityIdFormatter() {
		if ( !$this->entityIdFormatter ) {
			$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();
			$this->entityIdFormatter = new EntityIdPlainLinkFormatter( $titleLookup );
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
			$options->setOption( 'formatter-builders-text/plain', [
				'VT:wikibase-entityid' => function() use ( $idFormatter ) {
					return new EntityIdValueFormatter( $idFormatter );
				}
			] );

			$factory = WikibaseRepo::getDefaultInstance()->getValueFormatterFactory();
			$this->propertyValueFormatter = $factory->getValueFormatter( SnakFormatter::FORMAT_PLAIN, $options );
		}

		return $this->propertyValueFormatter;
	}

}
