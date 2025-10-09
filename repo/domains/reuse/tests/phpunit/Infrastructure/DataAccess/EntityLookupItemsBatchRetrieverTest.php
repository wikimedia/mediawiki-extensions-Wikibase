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

	public function testGetItems(): void {
		$deletedItem = new ItemId( 'Q666' );
		$item1Id = new ItemId( 'Q123' );
		$item1EnLabel = 'potato';
		$item1EnDescription = 'root vegetable';
		$item1EnAliases = [ 'spud', 'tater' ];
		$item1StatementGuid = "$item1Id\$bed933b7-4207-d679-7571-3630cfb49d7f";
		$item1StatementPropertyId = 'P1';
		$item1Statement = NewStatement::noValueFor( $item1StatementPropertyId )->withGuid( $item1StatementGuid )->build();
		$item1SitelinkSiteId = 'examplewiki';
		$item1SitelinkTitle = 'Potato';
		$item2Id = new ItemId( 'Q321' );

		$sitelinkSite = new MediaWikiSite();
		$sitelinkSite->setLinkPath( 'https://wiki.example/wiki/$1' );
		$expectedSitelinkUrl = "https://wiki.example/wiki/$item1SitelinkTitle";
		$sitelinkSite->setGlobalId( $item1SitelinkSiteId );

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $item1StatementPropertyId ), 'string' );

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $item1Id )
				->andLabel( 'en', $item1EnLabel )
				->andDescription( 'en', $item1EnDescription )
				->andAliases( 'en', $item1EnAliases )
				->andSiteLink( $item1SitelinkSiteId, $item1SitelinkTitle )
				->andStatement( $item1Statement )
				->build()
		);
		$entityLookup->addEntity( NewItem::withId( $item2Id )->build() );

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [ $sitelinkSite ] ), $dataTypeLookup )
			->getItems( $item1Id, $item2Id, $deletedItem );

		$this->assertEquals( $item1Id, $batch->getItem( $item1Id )->id );
		$this->assertSame(
			$item1EnLabel,
			$batch->getItem( $item1Id )->labels->getLabelInLanguage( 'en' )->text
		);
		$this->assertSame(
			$item1EnDescription,
			$batch->getItem( $item1Id )->descriptions->getDescriptionInLanguage( 'en' )->text
		);
		$this->assertSame(
			$item1EnAliases,
			$batch->getItem( $item1Id )->aliases->getAliasesInLanguageInLanguage( 'en' )->aliases
		);
		$this->assertEquals(
			new Sitelink(
				$item1SitelinkSiteId,
				$item1SitelinkTitle,
				$expectedSitelinkUrl,
			),
			$batch->getItem( $item1Id )->sitelinks->getSitelinkForSite( $item1SitelinkSiteId )
		);
		$statements = $batch->getItem( $item1Id )
			->statements->getStatementsByPropertyId( new NumericPropertyId( $item1StatementPropertyId ) );
		$this->assertCount( 1, $statements );

		$this->assertSame(
			$item1Statement->getGuid(),
			$statements[0]->id->getSerialization()
		);

		$this->assertSame(
			'string',
			$statements[0]->property->dataType
		);

		$this->assertEquals( $item2Id, $batch->getItem( $item2Id )->id );
		$this->assertNull( $batch->getItem( $deletedItem ) );
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
