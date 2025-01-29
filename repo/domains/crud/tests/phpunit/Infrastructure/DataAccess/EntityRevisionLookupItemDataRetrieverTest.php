<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use Generator;
use MediaWiki\Site\Site;
use MediaWiki\Site\SiteLookup;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item as ItemWriteModel;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Aliases;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Descriptions;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Item;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemParts;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\ItemPartsBuilder;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Labels;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\StatementList;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityRevisionLookupItemDataRetriever;
use Wikibase\Repo\Domains\Crud\Infrastructure\SitelinksReadModelConverter;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\EntityRevisionLookupItemDataRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupItemDataRetrieverTest extends TestCase {

	use StatementReadModelHelper;

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
			new StatementList( self::newStatementReadModelConverter()->convert( $expectedStatement ) ),
			$itemParts->getStatements()
		);
		$this->assertEquals(
			$this->newSitelinksReadModelConverter()->convert( $item->getSiteLinkList() ),
			$itemParts->getSitelinks()
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
	public function testGivenFields_getItemPartsReturnsOnlyRequestFields(
		ItemWriteModel $item,
		array $fields,
		callable $itemPartsFactory
	): void {
		$itemParts = $itemPartsFactory( $this );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $item->getId(), $item );

		$this->assertEquals(
			$itemParts,
			$this->newRetriever()->getItemParts( $item->getId(), $fields )
		);
	}

	public static function itemPartsWithFieldsProvider(): Generator {
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
			fn() => ( new ItemPartsBuilder( $item->getId(), $fields ) )
				->setLabels( Labels::fromTermList( $item->getLabels() ) )
				->setDescriptions( Descriptions::fromTermList( $item->getDescriptions() ) )
				->setAliases( Aliases::fromAliasGroupList( $item->getAliasGroups() ) )
				->build(),
		];
		yield 'statements only' => [
			$item,
			[ ItemParts::FIELD_STATEMENTS ],
			fn() => ( new ItemPartsBuilder( $item->getId(), [ ItemParts::FIELD_STATEMENTS ] ) )
				->setStatements( new StatementList( self::newStatementReadModelConverter()->convert( $statement ) ) )
				->build(),
		];
		yield 'all fields' => [
			$item,
			ItemParts::VALID_FIELDS,
			fn( self $self ) => ( new ItemPartsBuilder( $item->getId(), ItemParts::VALID_FIELDS ) )
				->setLabels( Labels::fromTermList( $item->getLabels() ) )
				->setDescriptions( Descriptions::fromTermList( $item->getDescriptions() ) )
				->setAliases( Aliases::fromAliasGroupList( $item->getAliasGroups() ) )
				->setStatements( new StatementList( self::newStatementReadModelConverter()->convert( $statement ) ) )
				->setSitelinks( $self->newSitelinksReadModelConverter()->convert( $item->getSiteLinkList() ) )
				->build(),
		];
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
				self::newStatementReadModelConverter()->convert( $statement1 ),
				self::newStatementReadModelConverter()->convert( $statement2 )
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
			new StatementList( self::newStatementReadModelConverter()->convert( $statement1 ) ),
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

	public function testGetItemWriteModel(): void {
		$itemId = new ItemId( 'Q321' );
		$itemWriteModel = NewItem::withId( $itemId )->build();
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $itemWriteModel );

		$this->assertSame( $itemWriteModel, $this->newRetriever()->getItemWriteModel( $itemId ) );
	}

	public function testGivenItemDoesNotExist_getItemWriteModelReturnsNull(): void {
		$itemId = new ItemId( 'Q666' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, null );

		$this->assertNull( $this->newRetriever()->getItemWriteModel( $itemId ) );
	}

	public function testGivenItemRedirected_getItemWriteModelReturnsNull(): void {
		$itemId = new ItemId( 'Q666' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithRedirect( $itemId );

		$this->assertNull( $this->newRetriever()->getItemWriteModel( $itemId ) );
	}

	public function testGetItem(): void {
		$itemId = new ItemId( 'Q321' );
		$itemWriteModel = NewItem::withId( $itemId )->build();
		$expectedItem = new Item(
			$itemId,
			new Labels(),
			new Descriptions(),
			new Aliases(),
			new Sitelinks(),
			new StatementList()
		);
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $itemWriteModel );

		$this->assertEquals( $expectedItem, $this->newRetriever()->getItem( $itemId ) );
	}

	public function testGivenItemNotFound_getItemReturnsNull(): void {
		$id = new ItemId( 'Q123' );
		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $id, null );

		$this->assertNull( $this->newRetriever()->getItem( $id ) );
	}

	public function testGetSitelinks(): void {
		$itemId = new ItemId( 'Q123' );
		$deSiteId = 'dewiki';
		$dePageName = 'Kartoffel';

		$enSiteId = 'enwiki';
		$enPageName = 'Potato';

		$badges = [ new ItemId( 'Q1' ) ];

		$item = NewItem::withId( $itemId )
			->andSiteLink( $deSiteId, $dePageName, $badges )
			->andSiteLink( $enSiteId, $enPageName, $badges )
			->build();

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $item );

		$sitelinks = $this->newRetriever()->getSitelinks( $itemId );

		$this->assertEquals(
			$this->newSitelinksReadModelConverter()->convert(
				new SiteLinkList( [
					new SiteLink( $deSiteId, $dePageName, $badges ),
					new SiteLink( $enSiteId, $enPageName, $badges ),
				] )
			),
			$sitelinks
		);
	}

	public function testGivenItemHasNoSitelinks_returnsEmptySitelinks(): void {
		$itemId = new ItemId( 'Q123' );

		$item = NewItem::withId( $itemId )->build();

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $item );

		$sitelinks = $this->newRetriever()->getSitelinks( $itemId );

		$this->assertEquals( new Sitelinks(), $sitelinks );
	}

	public function testGetSitelink(): void {
		$itemId = new ItemId( 'Q123' );
		$siteId = 'enwiki';
		$pageName = 'potato';
		$badges = [ new ItemId( 'Q1' ) ];

		$item = NewItem::withId( $itemId )->andSiteLink( $siteId, $pageName, $badges )->build();

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $item );

		$sitelinks = $this->newSitelinksReadModelConverter()->convert(
			new SiteLinkList( [ new SiteLink( $siteId, $pageName, $badges ) ] )
		);
		$this->assertEquals(
			$sitelinks[ $siteId ],
			$this->newRetriever()->getSitelink( $itemId, $siteId )
		);
	}

	public function testGivenItemHasNoSitelinksForRequestedSite_returnsNull(): void {
		$itemId = new ItemId( 'Q123' );

		$item = NewItem::withId( $itemId )->build();

		$this->entityRevisionLookup = $this->newEntityRevisionLookupForIdWithReturnValue( $itemId, $item );

		$this->assertNull( $this->newRetriever()->getSitelink( $itemId, 'enwiki' ) );
	}

	private function newRetriever(): EntityRevisionLookupItemDataRetriever {
		return new EntityRevisionLookupItemDataRetriever(
			$this->entityRevisionLookup,
			self::newStatementReadModelConverter(),
			$this->newSitelinksReadModelConverter()
		);
	}

	private function newEntityRevisionLookupForIdWithReturnValue( ItemId $id, ?ItemWriteModel $returnValue ): EntityRevisionLookup {
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

	private function newSitelinksReadModelConverter(): SitelinksReadModelConverter {
		$site = new Site();
		$site->setLinkPath( 'https://example.com/wiki/$1' );

		$siteLookup = $this->createStub( SiteLookup::class );
		$siteLookup->method( 'getSite' )->willReturn( $site );

		return new SitelinksReadModelConverter( $siteLookup );
	}

}
