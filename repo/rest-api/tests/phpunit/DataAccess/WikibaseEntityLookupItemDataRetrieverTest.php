<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Statement\Statement as DataModelStatement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Statement;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemDataRetrieverTest extends TestCase {

	public function testGetItemData(): void {
		$itemId = new ItemId( 'Q123' );
		$item = NewItem::withId( $itemId )->build();

		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $itemId, $item )
		);

		$itemData = $retriever->getItemData( $itemId, ItemData::VALID_FIELDS );

		$this->assertSame( $itemId, $itemData->getId() );
		$this->assertSame( $item->getLabels(), $itemData->getLabels() );
		$this->assertSame( $item->getDescriptions(), $itemData->getDescriptions() );
		$this->assertSame( $item->getAliasGroups(), $itemData->getAliases() );
		$this->assertSame( $item->getStatements(), $itemData->getStatements() );
		$this->assertSame( $item->getSiteLinkList(), $itemData->getSiteLinks() );
	}

	public function testGivenItemDoesNotExist_getItemDataReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $itemId, null )
		);

		$this->assertNull( $retriever->getItemData( $itemId, ItemData::VALID_FIELDS ) );
	}

	/**
	 * @dataProvider itemDataWithFieldsProvider
	 */
	public function testGivenFields_getItemDataReturnsItemDataOnlyWithRequestFields( Item $item, array $fields, ItemData $itemData ): void {
		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $item->getId(), $item )
		);

		$this->assertEquals(
			$itemData,
			$retriever->getItemData( $item->getId(), $fields )
		);
	}

	public function itemDataWithFieldsProvider(): Generator {
		$item = NewItem::withId( 'Q666' )
			->andLabel( 'en', 'potato' )
			->andDescription( 'en', 'root vegetable' )
			->andAliases( 'en', [ 'spud', 'tater' ] )
			->andStatement( NewStatement::someValueFor( 'P123' ) )
			->andSiteLink( 'dewiki', 'Kartoffel' )
			->build();

		yield 'type only' => [
			$item,
			[ ItemData::FIELD_TYPE ],
			( new ItemDataBuilder( $item->getId(), [ ItemData::FIELD_TYPE ] ) )
				->setType( Item::ENTITY_TYPE )
				->build(),
		];
		$fields = [ ItemData::FIELD_LABELS, ItemData::FIELD_DESCRIPTIONS, ItemData::FIELD_ALIASES ];
		yield 'labels, descriptions, aliases' => [
			$item,
			$fields,
			( new ItemDataBuilder( $item->getId(), $fields ) )
				->setLabels( $item->getLabels() )
				->setDescriptions( $item->getDescriptions() )
				->setAliases( $item->getAliasGroups() )
				->build(),
		];
		yield 'statements only' => [
			$item,
			[ ItemData::FIELD_STATEMENTS ],
			( new ItemDataBuilder( $item->getId(), [ ItemData::FIELD_STATEMENTS ] ) )
				->setStatements( $item->getStatements() )
				->build(),
		];
		yield 'all fields' => [
			$item,
			ItemData::VALID_FIELDS,
			( new ItemDataBuilder( $item->getId(), ItemData::VALID_FIELDS ) )
				->setType( Item::ENTITY_TYPE )
				->setLabels( $item->getLabels() )
				->setDescriptions( $item->getDescriptions() )
				->setAliases( $item->getAliasGroups() )
				->setStatements( $item->getStatements() )
				->setSiteLinks( $item->getSiteLinkList() )
				->build(),
		];
	}

	public function testGetStatement(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );

		$statement = NewStatement::forProperty( 'P123' )
			->withValue( 'potato' )
			->withGuid( $statementId )
			->build();
		$item = NewItem::withId( $itemId )
			->andStatement( $statement )
			->build();

		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $itemId, $item )
		);

		$this->assertEquals(
			$this->convertDataModelToReadModel( $statement ),
			$retriever->getStatement( $statementId )
		);
	}

	public function testGivenItemDoesNotExist_getStatementReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );

		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $itemId, null )
		);

		$this->assertNull( $retriever->getStatement( $statementId ) );
	}

	public function testGivenStatementDoesNotExist_getStatementReturnsNull(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );

		$item = NewItem::withId( $itemId )
			->build();

		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $itemId, $item )
		);

		$this->assertNull( $retriever->getStatement( $statementId ) );
	}

	public function testGetStatements(): void {
		$statement1 = NewStatement::forProperty( 'P123' )
			->withGuid( 'Q123$c48c32c3-42b5-498f-9586-84608b88747c' )
			->withValue( 'potato' )
			->build();
		$statement2 = NewStatement::forProperty( 'P321' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->withValue( 'banana' )
			->build();

		$item = NewItem::withId( 'Q123' )
			->andStatement( $statement1 )
			->andStatement( $statement2 )
			->build();

		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $item->getId(), $item )
		);

		$this->assertEquals(
			new StatementList(
				$this->convertDataModelToReadModel( $statement1 ),
				$this->convertDataModelToReadModel( $statement2 )
			),
			$retriever->getStatements( $item->getId() )
		);
	}

	public function testGivenItemDoesNotExist_getStatementsReturnsNull(): void {
		$nonexistentItemId = new ItemId( 'Q321' );
		$entityLookup = $this->newEntityLookupForIdWithReturnValue( $nonexistentItemId, null );

		$retriever = new WikibaseEntityLookupItemDataRetriever( $entityLookup );

		$this->assertNull( $retriever->getStatements( $nonexistentItemId ) );
	}

	public function testGetItem(): void {
		$itemId = new ItemId( 'Q321' );
		$item = NewItem::withId( $itemId )->build();
		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $itemId, $item )
		);

		$this->assertSame( $item, $retriever->getItem( $itemId ) );
	}

	public function testGivenItemDoesNotExist_getItemReturnsNull(): void {
		$itemId = new ItemId( 'Q666' );
		$retriever = new WikibaseEntityLookupItemDataRetriever(
			$this->newEntityLookupForIdWithReturnValue( $itemId, null )
		);

		$this->assertNull( $retriever->getItem( $itemId ) );
	}

	private function newEntityLookupForIdWithReturnValue( ItemId $id, ?Item $returnValue ): EntityLookup {
		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $id )
			->willReturn( $returnValue );

		return $entityLookup;
	}

	private function convertDataModelToReadModel( DataModelStatement $statement ): Statement {
		[ $itemId, $guidPart ] = explode( '$', $statement->getGuid() );
		return new Statement(
			new StatementGuid( new ItemId( $itemId ), $guidPart ),
			$statement->getRank(),
			$statement->getMainSnak(),
			$statement->getQualifiers(),
			$statement->getReferences()
		);
	}

}
