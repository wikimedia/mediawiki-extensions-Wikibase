<?php

namespace Wikibase\Repo\Tests\Api;

use DataValues\StringValue;
use FormatJson;
use MediaWiki\Storage\RevisionRecord;
use ApiUsageException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
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
 * @license GPL-2.0-or-later
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
	 * @param EntityDocument|StatementListProvider $entity
	 * @param PropertyId $propertyId
	 *
	 * @return EntityDocument|StatementListProvider
	 */
	private function addStatementsAndSave( EntityDocument $entity, PropertyId $propertyId ) {
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $entity, '', $GLOBALS['wgUser'], EDIT_NEW );

		$snak = new PropertyValueSnak( $propertyId, new StringValue( 'o_O' ) );
		$guid = $entity->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P';
		$entity->getStatements()->addNewStatement( $snak, null, null, $guid );

		$store->saveEntity( $entity, '', $GLOBALS['wgUser'], EDIT_UPDATE );

		return $entity;
	}

	public function testValidRequests() {
		$argLists = [];

		$property = Property::newFromType( 'commonsMedia' );

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $property, '', $GLOBALS['wgUser'], EDIT_NEW );

		$entity = $this->addStatementsAndSave( new Item(), $property->getId() );

		foreach ( $entity->getStatements()->toArray() as $statement ) {
			$value = new StringValue( 'Kittens.png' );
			$argLists[] = [
				'entityId' => $entity->getId(),
				'guid' => $statement->getGuid(),
				'value' => $value->getArrayValue(),
				'expectedSummary' => $this->getExpectedSummary( $statement, $value )
			];
		}

		foreach ( $argLists as $argList ) {
			call_user_func_array( [ $this, 'doTestValidRequest' ], $argList );
		}
	}

	public function doTestValidRequest( EntityId $entityId, $guid, $value, $expectedSummary ) {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$entityLookup = $wikibaseRepo->getEntityLookup();
		/** @var StatementListProvider $obtainedEntity */
		$obtainedEntity = $entityLookup->getEntity( $entityId );
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

		$obtainedEntity = $entityLookup->getEntity( $entityId );

		$page = new WikiPage( $wikibaseRepo->getEntityTitleLookup()->getTitleForId( $entityId ) );
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
	public function testInvalidRequest( $handle, $guid, $snakType, $value, $error ) {
		$entityId = new ItemId( EntityTestHelper::getId( $handle ) );
		$entity = WikibaseRepo::getDefaultInstance()->getEntityLookup()->getEntity( $entityId );

		if ( $guid === null ) {
			/** @var StatementListProvider $entity */
			$statements = $entity->getStatements()->toArray();
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
	private function getEntityIdFormatter() {
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
	private function getPropertyValueFormatter() {
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
