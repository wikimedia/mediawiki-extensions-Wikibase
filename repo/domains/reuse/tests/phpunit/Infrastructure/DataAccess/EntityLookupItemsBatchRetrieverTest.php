<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Reuse\Infrastructure\DataAccess;

use DataValues\StringValue;
use MediaWiki\Site\HashSiteStore;
use MediaWiki\Site\MediaWikiSite;
use MediaWiki\Site\SiteLookup;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
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
		$item1StatementPropertyId = 'P1';
		$item1StatementQualifierPropertyId = new NumericPropertyId( 'P42' );
		$item1Statement = NewStatement::noValueFor( $item1StatementPropertyId )
			->withSubject( $this->item1Id )
			->withSomeGuid()
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

		$qualifiers = $statements[0]->qualifiers->getQualifiersByPropertyId( $item1StatementQualifierPropertyId );
		$this->assertCount( 1, $qualifiers );

		$this->assertSame(
			$item1StatementQualifierPropertyId,
			$qualifiers[0]->property->id
		);

		$this->assertSame(
			'stringValue',
			$qualifiers[0]->value->content->getValue()
		);

		$this->assertSame(
			'value',
			$qualifiers[0]->valueType->value
		);

		$this->assertEquals( $this->item2Id, $batch->getItem( $this->item2Id )->id );
		$this->assertNull( $batch->getItem( $this->deletedItem ) );
	}

	public function testGetItemsWithStatementsWithReferences(): void {
		$item1StatementPropertyId = 'P1';
		$item1StatementReferencePropertyId = new NumericPropertyId( 'P42' );
		$item1Statement = NewStatement::noValueFor( $item1StatementPropertyId )
			->withSubject( $this->item1Id )
			->withSomeGuid()
			->withReference( new Reference( [ new PropertySomeValueSnak( $item1StatementReferencePropertyId ) ] ) )
			->build();

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $item1StatementPropertyId ), 'string' );

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $this->item1Id )
				->andStatement( $item1Statement )
				->build()
		);

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [] ), $dataTypeLookup )
			->getItems( $this->item1Id, $this->item2Id );

		$this->assertEquals( $this->item1Id, $batch->getItem( $this->item1Id )->id );

		$statements = $batch->getItem( $this->item1Id )
			->statements->getStatementsByPropertyId( new NumericPropertyId( $item1StatementPropertyId ) );
		$this->assertCount( 1, $statements );

		$references = $statements[0]->references;
		$this->assertCount( 1, $references );

		$this->assertSame(
			$item1StatementReferencePropertyId,
			$references[0]->parts[0]->property->id
		);

		$this->assertSame(
			null,
			$references[0]->parts[0]->value
		);

		$this->assertSame(
			'somevalue',
			$references[0]->parts[0]->valueType->value
		);
	}

	public function testGetItemWithStatementsWithValue(): void {
		$item1StatementPropertyId = 'P1';
		$item1Statement2PropertyId = 'P3';
		$itemValueItemId = 'Q6';
		$item1Statement = NewStatement::forProperty( $item1StatementPropertyId )
			->withSubject( $this->item1Id )
			->withSomeGuid()
			->withValue( 'stringValue' )
			->build();
		$item1Statement2 = NewStatement::forProperty( $item1Statement2PropertyId )
			->withSubject( $this->item1Id )
			->withSomeGuid()
			->withValue( new ItemId( $itemValueItemId ) )
			->build();

		$dataTypeLookup = new InMemoryDataTypeLookup();
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $item1StatementPropertyId ), 'string' );
		$dataTypeLookup->setDataTypeForProperty( new NumericPropertyId( $item1Statement2PropertyId ), 'wikibase-item' );

		$entityLookup = new InMemoryEntityLookup();
		$entityLookup->addEntity(
			NewItem::withId( $this->item1Id )
				->andStatement( $item1Statement )
				->andStatement( $item1Statement2 )
				->build()
		);

		$batch = $this->newRetriever( $entityLookup, new HashSiteStore( [] ), $dataTypeLookup )
			->getItems( $this->item1Id, $this->item2Id, $this->deletedItem );

		$this->assertEquals( $this->item1Id, $batch->getItem( $this->item1Id )->id );

		$statementWithStringValue = $batch->getItem( $this->item1Id )
			->statements->getStatementsByPropertyId( new NumericPropertyId( $item1StatementPropertyId ) );
		$this->assertCount( 1, $statementWithStringValue );
		$statementWithItemValue = $batch->getItem( $this->item1Id )
			->statements->getStatementsByPropertyId( new NumericPropertyId( $item1Statement2PropertyId ) );
		$this->assertCount( 1, $statementWithItemValue );

		$this->assertSame(
			$item1Statement->getGuid(),
			$statementWithStringValue[0]->id->getSerialization()
		);

		$this->assertSame(
			'stringValue',
			$statementWithStringValue[0]->value->content->getValue()
		);

		$this->assertSame(
			'value',
			$statementWithStringValue[0]->valueType->value
		);

		$this->assertSame(
			$item1Statement2->getGuid(),
			$statementWithItemValue[0]->id->getSerialization()
		);

		$this->assertSame(
			$itemValueItemId,
			$statementWithItemValue[0]->value->content->getEntityId()->getSerialization()
		);

		$this->assertSame(
			'value',
			$statementWithItemValue[0]->valueType->value
		);
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
