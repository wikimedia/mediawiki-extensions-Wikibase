<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess;

use DataValues\StringValue;
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
use Wikibase\Repo\Domains\Reuse\Domain\Model\Sitelink;
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
	//TODO: refactor this test
	private ItemId $item1Id;
	private ItemId $deletedItem;
	private ItemId $item2Id;

	protected function setUp(): void {
		parent::setUp();
		$this->deletedItem = new ItemId( 'Q666' );
		$this->item1Id = new ItemId( 'Q123' );
		$this->item2Id = new ItemId( 'Q321' );
	}

	public function testGetItemsWithLabelsAndDescriptions(): void {
		$item1EnLabel = 'potato';
		$item1EnDescription = 'root vegetable';
		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $this->item1Id )
				->andLabel( 'en', $item1EnLabel )
				->andDescription( 'en', $item1EnDescription )
				->build()
		);
		$entityLookup->addEntity( NewItem::withId( $this->item2Id )->build() );
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [] ), $dataTypeLookup )
			->getItems( $this->item1Id, $this->item2Id, $this->deletedItem );

		$this->assertEquals( $this->item1Id, $batch->getItem( $this->item1Id )->id );

		$this->assertSame(
			$item1EnLabel,
			$batch->getItem( $this->item1Id )->labels->getLabelInLanguage( 'en' )->text
		);

		$this->assertSame(
			$item1EnDescription,
			$batch->getItem( $this->item1Id )->descriptions->getDescriptionInLanguage( 'en' )->text
		);
	}

	public function testGetItemsWithAliasesAndSitelinks(): void {
		$item1EnAliases = [ 'spud', 'tater' ];
		$item1SitelinkSiteId = 'examplewiki';
		$item1SitelinkTitle = 'Potato';

		$sitelinkSite = new MediaWikiSite();
		$sitelinkSite->setLinkPath( 'https://wiki.example/wiki/$1' );
		$expectedSitelinkUrl = "https://wiki.example/wiki/$item1SitelinkTitle";
		$sitelinkSite->setGlobalId( $item1SitelinkSiteId );

		$dataTypeLookup = new InMemoryDataTypeLookup();

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $this->item1Id )
				->andAliases( 'en', $item1EnAliases )
				->andSiteLink( $item1SitelinkSiteId, $item1SitelinkTitle )
				->build()
		);
		$entityLookup->addEntity( NewItem::withId( $this->item2Id )->build() );

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [ $sitelinkSite ] ), $dataTypeLookup )
			->getItems( $this->item1Id, $this->item2Id, $this->deletedItem );

		$this->assertEquals( $this->item1Id, $batch->getItem( $this->item1Id )->id );

		$this->assertSame(
			$item1EnAliases,
			$batch->getItem( $this->item1Id )->aliases->getAliasesInLanguageInLanguage( 'en' )->aliases
		);

		$this->assertEquals(
			new Sitelink(
				$item1SitelinkSiteId,
				$item1SitelinkTitle,
				$expectedSitelinkUrl,
			),
			$batch->getItem( $this->item1Id )->sitelinks->getSitelinkForSite( $item1SitelinkSiteId )
		);
	}

	public function testGetItemsWithStatements(): void {
		$item1StatementGuid = "$this->item1Id\$bed933b7-4207-d679-7571-3630cfb49d7f";
		$item1StatementPropertyId = 'P1';
		$item1StatementQualifierPropertyId = new NumericPropertyId( 'P42' );
		$item1Statement = NewStatement::noValueFor( $item1StatementPropertyId )
			->withGuid( $item1StatementGuid )
			->withRank( 0 )
			->withQualifier( $item1StatementQualifierPropertyId, new StringValue( 'stringValue' ) )
			->build();

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $item1StatementPropertyId ), 'string' );

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $this->item1Id )
				->andStatement( $item1Statement )
				->build()
		);
		$entityLookup->addEntity( NewItem::withId( $this->item2Id )->build() );

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [] ), $dataTypeLookup )
			->getItems( $this->item1Id, $this->item2Id, $this->deletedItem );

		$this->assertEquals( $this->item1Id, $batch->getItem( $this->item1Id )->id );

		$statements = $batch->getItem( $this->item1Id )
			->statements->getStatementsByPropertyId( new NumericPropertyId( $item1StatementPropertyId ) );
		$this->assertCount( 1, $statements );

		$this->assertSame(
			$item1Statement->getGuid(),
			$statements[0]->id->getSerialization()
		);

		$this->assertSame(
			$item1Statement->getRank(),
			$statements[0]->rank->asInt()
		);

		$this->assertSame(
			'string',
			$statements[0]->property->dataType
		);

		// Include the value in the qualifier during property value implementation.
		$qualifiers = $statements[0]->qualifiers->getQualifiersByPropertyId( $item1StatementQualifierPropertyId );
		$this->assertCount( 1, $qualifiers );

		$this->assertSame(
			$item1StatementQualifierPropertyId,
			$qualifiers[0]->property->id
		);

		$this->assertEquals( $this->item2Id, $batch->getItem( $this->item2Id )->id );
		$this->assertNull( $batch->getItem( $this->deletedItem ) );
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
