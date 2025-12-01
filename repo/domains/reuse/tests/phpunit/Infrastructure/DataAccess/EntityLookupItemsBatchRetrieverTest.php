<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess;

use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\SiteLookup;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\DataModel\Tests\NewStatement;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Aliases;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Descriptions;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Labels;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Sitelink;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Sitelinks;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statement;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Statements;
use Wikibase\Repo\Domains\Reuse\Domain\Services\StatementReadModelConverter;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityLookupItemsBatchRetrieverTest extends TestCase {
	private ItemId $itemId;

	protected function setUp(): void {
		parent::setUp();
		$this->itemId = new ItemId( 'Q123' );
	}

	public function testGetItemsWithLabelsAndDescriptions(): void {
		$item2Id = new ItemId( 'Q321' );
		$deletedItem = new ItemId( 'Q666' );
		$item1EnLabel = 'potato';
		$item1EnDescription = 'root vegetable';

		$dataTypeLookup = new InMemoryDataTypeLookup();

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $this->itemId )
				->andLabel( 'en', $item1EnLabel )
				->andDescription( 'en', $item1EnDescription )
				->build()
		);
		$entityLookup->addEntity( NewItem::withId( $item2Id )->build() );

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [] ), $dataTypeLookup )
			->getItems( $this->itemId, $item2Id, $deletedItem );

		$this->assertEquals( $this->itemId, $batch->getItem( $this->itemId )->id );
		$this->assertSame(
			$item1EnLabel,
			$batch->getItem( $this->itemId )->labels->getLabelInLanguage( 'en' )->text
		);
		$this->assertSame(
			$item1EnDescription,
			$batch->getItem( $this->itemId )->descriptions->getDescriptionInLanguage( 'en' )->text
		);

		$this->assertEquals( $item2Id, $batch->getItem( $item2Id )->id );
		$this->assertEquals( new Descriptions(), $batch->getItem( $item2Id )->descriptions );
		$this->assertEquals( new Labels(), $batch->getItem( $item2Id )->labels );
		$this->assertEquals( new Aliases(), $batch->getItem( $item2Id )->aliases );
		$this->assertEquals( new Sitelinks(), $batch->getItem( $item2Id )->sitelinks );
		$this->assertEquals( new Statements(), $batch->getItem( $item2Id )->statements );

		$this->assertNull( $batch->getItem( $deletedItem ) );
	}

	public function testGetItemsWithAliasesAndSitelinks(): void {
		$itemEnAliases = [ 'spud', 'tater' ];
		$itemSitelinkSiteId = 'examplewiki';
		$itemSitelinkTitle = 'Potato';

		$sitelinkSite = new MediaWikiSite();
		$sitelinkSite->setLinkPath( 'https://wiki.example/wiki/$1' );
		$expectedSitelinkUrl = "https://wiki.example/wiki/$itemSitelinkTitle";
		$sitelinkSite->setGlobalId( $itemSitelinkSiteId );

		$dataTypeLookup = new InMemoryDataTypeLookup();

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $this->itemId )
				->andAliases( 'en', $itemEnAliases )
				->andSiteLink( $itemSitelinkSiteId, $itemSitelinkTitle )
				->build()
		);

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [ $sitelinkSite ] ), $dataTypeLookup )
			->getItems( $this->itemId );

		$this->assertEquals( $this->itemId, $batch->getItem( $this->itemId )->id );

		$this->assertSame(
			$itemEnAliases,
			$batch->getItem( $this->itemId )->aliases->getAliasesInLanguageInLanguage( 'en' )->aliases
		);

		$this->assertEquals(
			new Sitelink( $itemSitelinkSiteId, $itemSitelinkTitle, $expectedSitelinkUrl, ),
			$batch->getItem( $this->itemId )->sitelinks->getSitelinkForSite( $itemSitelinkSiteId )
		);
	}

	public function testGetItemsWithStatements(): void {
		$itemStatementPropertyId = 'P1';
		$itemStatement = NewStatement::noValueFor( $itemStatementPropertyId )
			->withSubject( $this->itemId )
			->withSomeGuid()
			->withRank( 0 )
			->build();

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $itemStatementPropertyId ), 'string' );

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $this->itemId )
				->andStatement( $itemStatement )
				->build()
		);

		$statement = $this->createStub( Statement::class );
		$statementConvertor = $this->createMock( StatementReadModelConverter::class );
		$statementConvertor->expects( $this->once() )
			->method( 'convert' )
			->with( $itemStatement )
			->willReturn( $statement );

		$batch = ( new EntityLookupItemsBatchRetriever( $entityLookup, new HashSiteStore( [] ), $statementConvertor ) )
			->getItems( $this->itemId );

		$this->assertEquals( $this->itemId, $batch->getItem( $this->itemId )->id );

		$this->assertEquals( new Statements( $statement ), $batch->getItem( $this->itemId )->statements );
	}

	private function newRetriever(
		EntityLookup $entityLookup,
		SiteLookup $siteLookup,
		InMemoryDataTypeLookup $dataTypeLookup
	): EntityLookupItemsBatchRetriever {
		return new EntityLookupItemsBatchRetriever(
			$entityLookup,
			$siteLookup,
			new StatementReadModelConverter(
				WikibaseRepo::getStatementGuidParser(),
				$dataTypeLookup
			)
		);
	}

}
