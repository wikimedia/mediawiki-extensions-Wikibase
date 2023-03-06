<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use Generator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Site;
use SiteLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemData;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemDataBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class WikibaseEntityLookupItemDataRetrieverTest extends TestCase {

	/**
	 * @var MockObject|EntityLookup
	 */
	private $entityLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->entityLookup = $this->createStub( EntityLookup::class );
	}

	public function testGetItemData(): void {
		$itemId = new ItemId( 'Q123' );
		$expectedStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
		$item = NewItem::withId( $itemId )
			->andStatement( $expectedStatement )
			->build();
		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $itemId, $item );

		$itemData = $this->newRetriever()->getItemData( $itemId, ItemData::VALID_FIELDS );

		$this->assertSame( $itemId, $itemData->getId() );
		$this->assertEquals( Labels::fromTermList( $item->getLabels() ), $itemData->getLabels() );
		$this->assertEquals( Descriptions::fromTermList( $item->getDescriptions() ), $itemData->getDescriptions() );
		$this->assertEquals( Aliases::fromAliasGroupList( $item->getAliasGroups() ), $itemData->getAliases() );
		$this->assertEquals(
			new StatementList( $this->newStatementReadModelConverter()->convert( $expectedStatement ) ),
			$itemData->getStatements()
		);
		$this->assertEquals(
			$this->newSiteLinksReadModelConverter()->convert( $item->getSiteLinkList() ),
			$itemData->getSiteLinks()
		);
	}

	public function testGivenItemDoesNotExist_getItemDataReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $itemId, null );

		$this->assertNull( $this->newRetriever()->getItemData( $itemId, ItemData::VALID_FIELDS ) );
	}

	/**
	 * @dataProvider itemDataWithFieldsProvider
	 */
	public function testGivenFields_getItemDataReturnsItemDataOnlyWithRequestFields( Item $item, array $fields, ItemData $itemData ): void {
		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $item->getId(), $item );

		$this->assertEquals(
			$itemData,
			$this->newRetriever()->getItemData( $item->getId(), $fields )
		);
	}

	public function itemDataWithFieldsProvider(): Generator {
		$statement = NewStatement::someValueFor( 'P123' )
			->withGuid( 'Q666$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();

		$item = NewItem::withId( 'Q666' )
			->andLabel( 'en', 'potato' )
			->andDescription( 'en', 'root vegetable' )
			->andAliases( 'en', [ 'spud', 'tater' ] )
			->andStatement( $statement )
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
				->setLabels( Labels::fromTermList( $item->getLabels() ) )
				->setDescriptions( Descriptions::fromTermList( $item->getDescriptions() ) )
				->setAliases( Aliases::fromAliasGroupList( $item->getAliasGroups() ) )
				->build(),
		];
		yield 'statements only' => [
			$item,
			[ ItemData::FIELD_STATEMENTS ],
			( new ItemDataBuilder( $item->getId(), [ ItemData::FIELD_STATEMENTS ] ) )
				->setStatements( new StatementList( $this->newStatementReadModelConverter()->convert( $statement ) ) )
				->build(),
		];
		yield 'all fields' => [
			$item,
			ItemData::VALID_FIELDS,
			( new ItemDataBuilder( $item->getId(), ItemData::VALID_FIELDS ) )
				->setType( Item::ENTITY_TYPE )
				->setLabels( Labels::fromTermList( $item->getLabels() ) )
				->setDescriptions( Descriptions::fromTermList( $item->getDescriptions() ) )
				->setAliases( Aliases::fromAliasGroupList( $item->getAliasGroups() ) )
				->setStatements( new StatementList( $this->newStatementReadModelConverter()->convert( $statement ) ) )
				->setSiteLinks( $this->newSiteLinksReadModelConverter()->convert( $item->getSiteLinkList() ) )
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

		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $itemId, $item );

		$this->assertEquals(
			$this->newStatementReadModelConverter()->convert( $statement ),
			$this->newRetriever()->getStatement( $statementId )
		);
	}

	public function testGivenItemDoesNotExist_getStatementReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $itemId, null );

		$this->assertNull( $this->newRetriever()->getStatement( $statementId ) );
	}

	public function testGivenStatementDoesNotExist_getStatementReturnsNull(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );

		$item = NewItem::withId( $itemId )
			->build();

		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $itemId, $item );

		$this->assertNull( $this->newRetriever()->getStatement( $statementId ) );
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

		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $item->getId(), $item );

		$this->assertEquals(
			new StatementList(
				$this->newStatementReadModelConverter()->convert( $statement1 ),
				$this->newStatementReadModelConverter()->convert( $statement2 )
			),
			$this->newRetriever()->getStatements( $item->getId() )
		);
	}

	public function testGivenProperty_getStatementsReturnsStatementGroup(): void {
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

		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $item->getId(), $item );

		$this->assertEquals(
			new StatementList( $this->newStatementReadModelConverter()->convert( $statement1 ) ),
			$this->newRetriever()->getStatements( $item->getId(), new NumericPropertyId( 'P123' ) )
		);
	}

	public function testGivenItemDoesNotExist_getStatementsReturnsNull(): void {
		$nonexistentItemId = new ItemId( 'Q321' );
		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $nonexistentItemId, null );

		$this->assertNull( $this->newRetriever()->getStatements( $nonexistentItemId ) );
	}

	public function testGetItem(): void {
		$itemId = new ItemId( 'Q321' );
		$item = NewItem::withId( $itemId )->build();
		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $itemId, $item );

		$this->assertSame( $item, $this->newRetriever()->getItem( $itemId ) );
	}

	public function testGivenItemDoesNotExist_getItemReturnsNull(): void {
		$itemId = new ItemId( 'Q666' );
		$this->entityLookup = $this->newEntityLookupForIdWithReturnValue( $itemId, null );

		$this->assertNull( $this->newRetriever()->getItem( $itemId ) );
	}

	private function newRetriever(): WikibaseEntityLookupItemDataRetriever {
		return new WikibaseEntityLookupItemDataRetriever(
			$this->entityLookup,
			$this->newStatementReadModelConverter(),
			$this->newSiteLinksReadModelConverter()
		);
	}

	private function newEntityLookupForIdWithReturnValue( ItemId $id, ?Item $returnValue ): EntityLookup {
		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $id )
			->willReturn( $returnValue );

		return $entityLookup;
	}

	private function newStatementReadModelConverter(): StatementReadModelConverter {
		return new StatementReadModelConverter( WikibaseRepo::getStatementGuidParser() );
	}

	private function newSiteLinksReadModelConverter(): SiteLinksReadModelConverter {
		$site = new Site();
		$site->setLinkPath( 'https://example.com/wiki/$1' );

		$siteLookup = $this->createStub( SiteLookup::class );
		$siteLookup->method( 'getSite' )->willReturn( $site );

		return new SiteLinksReadModelConverter( $siteLookup );
	}

}
