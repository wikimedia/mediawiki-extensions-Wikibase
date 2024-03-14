<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Site;
use SiteLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\RestApi\Domain\Model\EditMetadata;
use Wikibase\Repo\RestApi\Domain\Model\EditSummary;
use Wikibase\Repo\RestApi\Domain\ReadModel\Aliases;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Item;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelink;
use Wikibase\Repo\RestApi\Domain\ReadModel\Sitelinks;
use Wikibase\Repo\RestApi\Domain\ReadModel\StatementList;
use Wikibase\Repo\RestApi\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdater;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterItemUpdater;
use Wikibase\Repo\RestApi\Infrastructure\SitelinksReadModelConverter;
use Wikibase\Repo\Tests\RestApi\Domain\ReadModel\NewStatementReadModel;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\EntityUpdaterItemUpdater
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityUpdaterItemUpdaterTest extends TestCase {

	use StatementReadModelHelper;

	private const EN_WIKI_URL_PREFIX = 'https://en.wikipedia.org/wiki/';
	private const DE_WIKI_URL_PREFIX = 'https://de.wikipedia.org/wiki/';

	private EntityUpdater $entityUpdater;
	private StatementReadModelConverter $statementReadModelConverter;

	protected function setUp(): void {
		parent::setUp();

		$this->statementReadModelConverter = $this->newStatementReadModelConverter();
		$this->entityUpdater = $this->createStub( EntityUpdater::class );
	}

	public function testCreate(): void {
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );
		$itemToCreate = NewItem::withLabel( 'en', 'English Label' )
			->andDescription( 'en', 'English Description' )
			->andAliases( 'en', [ 'English alias', 'alias in English' ] )
			->andSiteLink( 'enwiki', 'Title', [ 'Q789' ] )
			->build();

		$this->entityUpdater = $this->createMock( EntityUpdater::class );
		$this->entityUpdater->expects( $this->once() )
			->method( 'create' )
			->with( $itemToCreate, $editMetaData )
			->willReturnCallback( function () use ( $itemToCreate, $expectedRevisionId, $expectedRevisionTimestamp ) {
				$itemToCreate->setId( new ItemId( 'Q123' ) );
				return new EntityRevision( $itemToCreate, $expectedRevisionId, $expectedRevisionTimestamp );
			} );

		$itemRevision = $this->newItemUpdater()->create( $itemToCreate, $editMetaData );

		$this->assertEquals(
			new Item(
				$itemRevision->getItem()->getId(),
				new Labels( new Label( 'en', 'English Label' ) ),
				new Descriptions( new Description( 'en', 'English Description' ) ),
				new Aliases( new AliasesInLanguage( 'en', [ 'English alias', 'alias in English' ] ) ),
				new Sitelinks( new Sitelink( 'enwiki', 'Title', [ new ItemId( 'Q789' ) ], self::EN_WIKI_URL_PREFIX . 'Title' ) ),
				new StatementList()
			),
			$itemRevision->getItem()
		);
		$this->assertSame( $expectedRevisionId, $itemRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $itemRevision->getLastModified() );
	}

	public function testCreateWithId_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->newItemUpdater()->create(
			NewItem::withId( 'Q123' )->build(),
			new EditMetadata( [], true, $this->createStub( EditSummary::class ) )
		);
	}

	public function testUpdate(): void {
		$expectedRevisionId = 234;
		$expectedRevisionTimestamp = '20221111070707';
		$editMetaData = new EditMetadata( [], true, $this->createStub( EditSummary::class ) );
		[ $itemToUpdate, $expectedResultingItem ] = $this->newEquivalentWriteAndReadModelItem();

		$this->entityUpdater = $this->createMock( EntityUpdater::class );

		$this->entityUpdater->expects( $this->once() )
			->method( 'update' )
			->with( $itemToUpdate, $editMetaData )
			->willReturn( new EntityRevision( $itemToUpdate, $expectedRevisionId, $expectedRevisionTimestamp ) );

		$itemRevision = $this->newItemUpdater()->update( $itemToUpdate, $editMetaData );

		$this->assertEquals( $expectedResultingItem, $itemRevision->getItem() );
		$this->assertSame( $expectedRevisionId, $itemRevision->getRevisionId() );
		$this->assertSame( $expectedRevisionTimestamp, $itemRevision->getLastModified() );
	}

	public function testUpdateWithoutId_throws(): void {
		$this->expectException( InvalidArgumentException::class );
		$this->newItemUpdater()->update(
			NewItem::withLabel( 'en', 'udpated Item' )->build(),
			new EditMetadata( [], true, $this->createStub( EditSummary::class ) )
		);
	}

	private function newEquivalentWriteAndReadModelItem(): array {
		$writeModelStatement = NewStatement::someValueFor( 'P123' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();
		$readModelStatement = NewStatementReadModel::someValueFor( 'P123' )
			->withGuid( 'Q123$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE' )
			->build();

		return [
			NewItem::withId( 'Q123' )
				->andLabel( 'en', 'English Label' )
				->andDescription( 'en', 'English Description' )
				->andAliases( 'en', [ 'English alias', 'alias in English' ] )
				->andSiteLink( 'enwiki', 'Title', [ 'Q789' ] )
				->andStatement( $writeModelStatement )
				->build(),
			new Item(
				new ItemId( 'Q123' ),
				new Labels( new Label( 'en', 'English Label' ) ),
				new Descriptions( new Description( 'en', 'English Description' ) ),
				new Aliases( new AliasesInLanguage( 'en', [ 'English alias', 'alias in English' ] ) ),
				new Sitelinks( new Sitelink( 'enwiki', 'Title', [ new ItemId( 'Q789' ) ], self::EN_WIKI_URL_PREFIX . 'Title' ) ),
				new StatementList( $readModelStatement )
			),
		];
	}

	private function newItemUpdater(): EntityUpdaterItemUpdater {
		return new EntityUpdaterItemUpdater( $this->entityUpdater, $this->newSitelinkConverter(), $this->statementReadModelConverter );
	}

	private function newSitelinkConverter(): SitelinksReadModelConverter {
		$enSite = new Site();
		$enSite->setLinkPath( self::EN_WIKI_URL_PREFIX . '$1' );
		$deSite = new Site();
		$deSite->setLinkPath( self::DE_WIKI_URL_PREFIX . '$1' );

		$siteLookup = $this->createStub( SiteLookup::class );
		$siteLookup->method( 'getSite' )->willReturnMap( [
			[ 'enwiki', $enSite ],
			[ 'dewiki', $deSite ],
		] );

		return new SitelinksReadModelConverter( $siteLookup );
	}

}
