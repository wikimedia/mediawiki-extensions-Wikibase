<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess;

use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\SiteLookup;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\Domains\Reuse\Domain\Model\Sitelink;
use Wikibase\Repo\Domains\Reuse\Infrastructure\DataAccess\EntityLookupItemsBatchRetriever;

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
		$item1SitelinkSiteId = 'examplewiki';
		$item1SitelinkTitle = 'Potato';
		$item2Id = new ItemId( 'Q321' );

		$sitelinkSite = new MediaWikiSite();
		$sitelinkSite->setLinkPath( 'https://wiki.example/wiki/$1' );
		$expectedSitelinkUrl = "https://wiki.example/wiki/$item1SitelinkTitle";
		$sitelinkSite->setGlobalId( $item1SitelinkSiteId );

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $item1Id )
				->andLabel( 'en', $item1EnLabel )
				->andDescription( 'en', $item1EnDescription )
				->andAliases( 'en', $item1EnAliases )
				->andSiteLink( $item1SitelinkSiteId, $item1SitelinkTitle )
				->build()
		);
		$entityLookup->addEntity( NewItem::withId( $item2Id )->build() );

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [ $sitelinkSite ] ) )
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

		$this->assertEquals( $item2Id, $batch->getItem( $item2Id )->id );
		$this->assertNull( $batch->getItem( $deletedItem ) );
	}

	private function newRetriever( EntityLookup $entityLookup, SiteLookup $siteLookup ): EntityLookupItemsBatchRetriever {
		return new EntityLookupItemsBatchRetriever( $entityLookup, $siteLookup );
	}

}
