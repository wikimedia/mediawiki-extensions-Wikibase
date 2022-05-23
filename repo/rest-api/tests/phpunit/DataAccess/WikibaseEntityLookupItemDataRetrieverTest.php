<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\DataAccess;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Repo\RestApi\DataAccess\WikibaseEntityLookupItemDataRetriever;
use Wikibase\Repo\RestApi\Domain\Model\ItemData;
use Wikibase\Repo\RestApi\Domain\Model\ItemDataBuilder;
use Wikibase\Repo\Tests\NewItem;
use Wikibase\Repo\Tests\NewStatement;

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

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $itemId )
			->willReturn( $item );

		$retriever = new WikibaseEntityLookupItemDataRetriever( $entityLookup );

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

		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $itemId )
			->willReturn( null );

		$retriever = new WikibaseEntityLookupItemDataRetriever( $entityLookup );

		$this->assertNull( $retriever->getItemData( $itemId, ItemData::VALID_FIELDS ) );
	}

	/**
	 * @dataProvider itemDataWithFieldsProvider
	 */
	public function testGivenFields_getItemDataReturnsItemDataOnlyWithRequestFields( Item $item, array $fields, ItemData $itemData ): void {
		$entityLookup = $this->createMock( EntityLookup::class );
		$entityLookup->expects( $this->once() )
			->method( 'getEntity' )
			->with( $item->getId() )
			->willReturn( $item );

		$retriever = new WikibaseEntityLookupItemDataRetriever( $entityLookup );

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
			( new ItemDataBuilder() )->setId( $item->getId() )
				->setType( Item::ENTITY_TYPE )
				->build()
		];
		yield 'labels, descriptions, aliases' => [
			$item,
			[ ItemData::FIELD_LABELS, ItemData::FIELD_DESCRIPTIONS, ItemData::FIELD_ALIASES ],
			( new ItemDataBuilder() )->setId( $item->getId() )
				->setLabels( $item->getLabels() )
				->setDescriptions( $item->getDescriptions() )
				->setAliases( $item->getAliasGroups() )
				->build(),
		];
		yield 'statements only' => [
			$item,
			[ ItemData::FIELD_STATEMENTS ],
			( new ItemDataBuilder() )->setId( $item->getId() )
				->setStatements( $item->getStatements() )
				->build(),
		];
		yield 'all fields' => [
			$item,
			ItemData::VALID_FIELDS,
			( new ItemDataBuilder() )->setId( $item->getId() )
				->setType( Item::ENTITY_TYPE )
				->setLabels( $item->getLabels() )
				->setDescriptions( $item->getDescriptions() )
				->setAliases( $item->getAliasGroups() )
				->setStatements( $item->getStatements() )
				->setSiteLinks( $item->getSiteLinkList() )
				->build(),
		];
	}

}
