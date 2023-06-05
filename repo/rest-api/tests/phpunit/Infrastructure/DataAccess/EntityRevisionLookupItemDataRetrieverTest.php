<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Site;
use SiteLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemPartsBuilder;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Infrastructure\SiteLinksReadModelConverter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityRevisionLookupItemDataRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupItemDataRetrieverTest extends TestCase {

	private EntityRevisionLookup $entityRevisionLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->entityRevisionLookup = $this->createStub( EntityRevisionLookup::class );
	}

	public function testGetItemParts(): void {
		$itemId = new ItemId( 'Q123' );
		$expectedStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
		$item = NewItem::withId( $itemId )
			->andStatement( $expectedStatement )
			->build();
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $item );

		$itemParts = $this->newRetriever()->getItemParts( $itemId, ItemParts::VALID_FIELDS );

		$this->assertSame( $itemId, $itemParts->getId() );
		$this->assertEquals( Labels::fromTermList( $item->getLabels() ), $itemParts->getLabels() );
		$this->assertEquals( Descriptions::fromTermList( $item->getDescriptions() ), $itemParts->getDescriptions() );
		$this->assertEquals( Aliases::fromAliasGroupList( $item->getAliasGroups() ), $itemParts->getAliases() );
		$this->assertEquals(
			new StatementList( $this->newStatementReadModelConverter()->convert( $expectedStatement ) ),
			$itemParts->getStatements()
		);
		$this->assertEquals(
			$this->newSiteLinksReadModelConverter()->convert( $item->getSiteLinkList() ),
			$itemParts->getSiteLinks()
		);
	}

	public function testGivenItemDoesNotExist_getItemPartsReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, null );

		$this->assertNull( $this->newRetriever()->getItemParts( $itemId, ItemParts::VALID_FIELDS ) );
	}

	public function testGivenItemRedirected_getItemPartsReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithRedirect( $itemId );

		$this->assertNull( $this->newRetriever()->getItemParts( $itemId, ItemParts::VALID_FIELDS ) );
	}

	/**
	 * @dataProvider itemPartsWithFieldsProvider
	 */
	public function testGivenFields_getItemPartsReturnsOnlyRequestFields( Item $item, array $fields, ItemParts $itemParts ): void {
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $item->getId(), $item );

		$this->assertEquals(
			$itemParts,
			$this->newRetriever()->getItemParts( $item->getId(), $fields )
		);
	}

	public function itemPartsWithFieldsProvider(): Generator {
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

		$fields = [ ItemParts::FIELD_LABELS, ItemParts::FIELD_DESCRIPTIONS, ItemParts::FIELD_ALIASES ];
		yield 'labels, descriptions, aliases' => [
			$item,
			$fields,
			( new ItemPartsBuilder( $item->getId(), $fields ) )
				->setLabels( Labels::fromTermList( $item->getLabels() ) )
				->setDescriptions( Descriptions::fromTermList( $item->getDescriptions() ) )
				->setAliases( Aliases::fromAliasGroupList( $item->getAliasGroups() ) )
				->build(),
		];
		yield 'statements only' => [
			$item,
			[ ItemParts::FIELD_STATEMENTS ],
			( new ItemPartsBuilder( $item->getId(), [ ItemParts::FIELD_STATEMENTS ] ) )
				->setStatements( new StatementList( $this->newStatementReadModelConverter()->convert( $statement ) ) )
				->build(),
		];
		yield 'all fields' => [
			$item,
			ItemParts::VALID_FIELDS,
			( new ItemPartsBuilder( $item->getId(), ItemParts::VALID_FIELDS ) )
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

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $item );

		$this->assertEquals(
			$this->newStatementReadModelConverter()->convert( $statement ),
			$this->newRetriever()->getStatement( $statementId )
		);
	}

	public function testGivenItemDoesNotExist_getStatementReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, null );

		$this->assertNull( $this->newRetriever()->getStatement( $statementId ) );
	}

	public function testGivenItemRedirected_getStatementReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithRedirect( $itemId );

		$this->assertNull( $this->newRetriever()->getStatement( $statementId ) );
	}

	public function testGivenStatementDoesNotExist_getStatementReturnsNull(): void {
		$itemId = new ItemId( 'Q123' );
		$statementId = new StatementGuid( $itemId, 'c48c32c3-42b5-498f-9586-84608b88747c' );

		$item = NewItem::withId( $itemId )
			->build();

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $item );

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

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $item->getId(), $item );

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

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $item->getId(), $item );

		$this->assertEquals(
			new StatementList( $this->newStatementReadModelConverter()->convert( $statement1 ) ),
			$this->newRetriever()->getStatements( $item->getId(), new NumericPropertyId( 'P123' ) )
		);
	}

	public function testGivenItemDoesNotExist_getStatementsReturnsNull(): void {
		$nonexistentItemId = new ItemId( 'Q321' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $nonexistentItemId, null );

		$this->assertNull( $this->newRetriever()->getStatements( $nonexistentItemId ) );
	}

	public function testGivenItemRedirected_getStatementsReturnsNull(): void {
		$itemId = new ItemId( 'Q321' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithRedirect( $itemId );

		$this->assertNull( $this->newRetriever()->getStatements( $itemId ) );
	}

	public function testGetItem(): void {
		$itemId = new ItemId( 'Q321' );
		$item = NewItem::withId( $itemId )->build();
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $item );

		$this->assertSame( $item, $this->newRetriever()->getItem( $itemId ) );
	}

	public function testGivenItemDoesNotExist_getItemReturnsNull(): void {
		$itemId = new ItemId( 'Q666' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, null );

		$this->assertNull( $this->newRetriever()->getItem( $itemId ) );
	}

	public function testGivenItemRedirected_getItemReturnsNull(): void {
		$itemId = new ItemId( 'Q666' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithRedirect( $itemId );

		$this->assertNull( $this->newRetriever()->getItem( $itemId ) );
	}

	private function newRetriever(): EntityRevisionLookupItemDataRetriever {
		return new EntityRevisionLookupItemDataRetriever(
			$this->entityRevisionLookup,
			$this->newStatementReadModelConverter(),
			$this->newSiteLinksReadModelConverter()
		);
	}

	private function newEntityRevisionLookupForIdWithReturnValue( ItemId $id, ?Item $returnValue ): EntityRevisionLookup {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $id )
			->willReturn( $returnValue ? new EntityRevision( $returnValue ) : null );

		return $entityRevisionLookup;
	}

	private function newEntityRevisionLookupForIdWithRedirect( ItemId $id ): EntityRevisionLookup {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $id )
			->willThrowException( $this->createStub( RevisionedUnresolvedRedirectException::class ) );

		return $entityRevisionLookup;
	}

	private function newStatementReadModelConverter(): StatementReadModelConverter {
		return new StatementReadModelConverter( WikibaseRepo::getStatementGuidParser(), new InMemoryDataTypeLookup() );
	}

	private function newSiteLinksReadModelConverter(): SiteLinksReadModelConverter {
		$site = new Site();
		$site->setLinkPath( 'https://example.com/wiki/$1' );

		$siteLookup = $this->createStub( SiteLookup::class );
		$siteLookup->method( 'getSite' )->willReturn( $site );

		return new SiteLinksReadModelConverter( $siteLookup );
	}

}
