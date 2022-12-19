<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use DataValues\StringValue;
use FormatJson;
use MediaWiki\Revision\RevisionRecord;
use PHPUnit\Framework\Constraint\Constraint;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\StatementListProvidingEntity;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\Formatters\EntityIdPlainLinkFormatter;
use Wikibase\Lib\Formatters\EntityIdValueFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Repo\Api\SetClaimValue
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

	protected function setUp(): void {
		parent::setUp();

		static $hasEntities = false;

		if ( !$hasEntities ) {
			$this->initTestEntities( [ 'StringProp', 'Berlin' ] );
			$hasEntities = true;
		}
	}

	private function addStatementsAndSave(
		StatementListProvidingEntity $entity,
		PropertyId $propertyId
	): StatementListProvidingEntity {
		$store = $this->getEntityStore();
		$store->saveEntity( $entity, '', $this->user, EDIT_NEW );

		$snak = new PropertyValueSnak( $propertyId, new StringValue( 'o_O' ) );
		$guid = $entity->getId()->getSerialization() . '$D8404CDA-25E4-4334-AG93-A3290BCD9C0P';
		$entity->getStatements()->addNewStatement( $snak, null, null, $guid );

		$store->saveEntity( $entity, '', $this->user, EDIT_UPDATE );

		return $entity;
	}

	public function testValidRequests() {
		$argLists = [];

		$property = Property::newFromType( 'commonsMedia' );

		$store = $this->getEntityStore();
		$store->saveEntity( $property, '', $this->user, EDIT_NEW );

		$entity = $this->addStatementsAndSave( new Item(), $property->getId() );

		foreach ( $entity->getStatements()->toArray() as $statement ) {
			$value = new StringValue( 'Kittens.png' );
			$argLists[] = [
				$entity->getId(),
				$statement->getGuid(),
				$value->getArrayValue(),
				$this->getExpectedSummary( $statement, $value ),
			];
		}

		foreach ( $argLists as $argList ) {
			$this->doTestValidRequest( ...$argList );
		}
	}

	public function testSetClaimNewWithTag() {
		$property = Property::newFromType( 'commonsMedia' );

		$store = $this->getEntityStore();
		$store->saveEntity( $property, '', $this->user, EDIT_NEW );

		$entity = $this->addStatementsAndSave( new Item(), $property->getId() );

		$this->assertCanTagSuccessfulRequest( [
			'action' => 'wbsetclaimvalue',
			'claim' => $entity->getStatements()->toArray()[0]->getGuid(),
			'value' => FormatJson::encode( ( new StringValue( 'Kittens.png' ) )->getArrayValue() ),
			'snaktype' => 'value',
		] );
	}

	public function testReturnsNormalizedData(): void {
		$propertyId = $this->createUppercaseStringTestProperty();

		$entity = $this->addStatementsAndSave( new Item(), $propertyId );

		[ $response ] = $this->doApiRequestWithToken( [
			'action' => 'wbsetclaimvalue',
			'claim' => $entity->getStatements()->toArray()[0]->getGuid(),
			'snaktype' => 'value',
			'value' => '"a string"',
		] );

		$this->assertSame( 'A STRING', $response['claim']['mainsnak']['datavalue']['value'] );
	}

	public function doTestValidRequest( EntityId $entityId, string $guid, $value, string $expectedSummary ): void {
		$entityLookup = WikibaseRepo::getEntityLookup();
		/** @var StatementListProvidingEntity $obtainedEntity */
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
		$this->assertIsArray( $resultArray, 'top level element is an array' );
		$this->assertArrayHasKey( 'claim', $resultArray, 'top level element has a claim key' );

		$claim = $resultArray['claim'];

		$this->assertEquals( $value, $claim['mainsnak']['datavalue']['value'] );

		$obtainedEntity = $entityLookup->getEntity( $entityId );

		$page = $this->getServiceContainer()->getWikiPageFactory()
			->newFromTitle( WikibaseRepo::getEntityTitleStoreLookup()->getTitleForId( $entityId ) );

		$comment = $page->getRevisionRecord()->getComment( RevisionRecord::RAW );
		$generatedSummary = $comment ? $comment->text : null;
		$this->assertEquals( $expectedSummary, $generatedSummary, 'Summary mismatch' );

		$statements = $obtainedEntity->getStatements();

		$this->assertSame(
			$statementCount,
			$statements->count(),
			'Statement count should not change after doing a setclaimvalue request'
		);

		$obtainedClaim = $statements->getFirstStatementWithGuid( $guid );

		$this->assertNotNull( $obtainedClaim );

		$dataValue = WikibaseRepo::getDataValueFactory()->newFromArray( $claim['mainsnak']['datavalue'] );

		$this->assertTrue( $obtainedClaim->getMainSnak()->getDataValue()->equals( $dataValue ) );
	}

	/**
	 * @dataProvider invalidRequestProvider
	 */
	public function testInvalidRequest( string $handle, ?string $guid, string $snakType, $value, $error ) {
		$entityId = new ItemId( EntityTestHelper::getId( $handle ) );
		$entity = WikibaseRepo::getEntityLookup()->getEntity( $entityId );

		if ( $guid === null ) {
			/** @var StatementListProvidingEntity $entity */
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
			$this->assertThat(
				$msg->getApiCode(),
				$error instanceof Constraint ? $error : $this->equalTo( $error ),
				'Invalid request raised correct error'
			);
		}
	}

	public function invalidRequestProvider(): iterable {
		return [
			'bad guid 1' => [ 'Berlin', 'xyz', 'value', 'abc', 'invalid-guid' ],
			'bad guid 2' => [ 'Berlin', 'x$y$z', 'value', 'abc', 'invalid-guid' ],
			'bad guid 3' => [ 'Berlin', 'i1813$358fa2a0-4345-82b6-12a4-7b0fee494a5f', 'value', 'abc', 'invalid-guid' ],
			'bad snak type' => [ 'Berlin', null, 'alksdjf', 'abc', $this->logicalOr(
				$this->equalTo( 'unknown_snaktype' ),
				$this->equalTo( 'badvalue' )
			) ],
			'bad snak value' => [ 'Berlin', null, 'value', '    ', 'invalid-snak' ],
		];
	}

	private function getExpectedSummary( Statement $oldStatement, StringValue $value ): string {
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
	private function getEntityIdFormatter(): EntityIdFormatter {
		if ( !$this->entityIdFormatter ) {
			$titleLookup = WikibaseRepo::getEntityTitleLookup();
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
	private function getPropertyValueFormatter(): ValueFormatter {
		if ( !$this->propertyValueFormatter ) {
			$idFormatter = $this->getEntityIdFormatter();

			$options = new FormatterOptions();
			$options->setOption( 'formatter-builders-text/plain', [
				'VT:wikibase-entityid' => function() use ( $idFormatter ) {
					return new EntityIdValueFormatter( $idFormatter );
				},
			] );

			$factory = WikibaseRepo::getValueFormatterFactory();
			$this->propertyValueFormatter = $factory->getValueFormatter( SnakFormatter::FORMAT_PLAIN, $options );
		}

		return $this->propertyValueFormatter;
	}

}
