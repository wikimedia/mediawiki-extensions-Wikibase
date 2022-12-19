<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use MediaWikiIntegrationTestCase;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ReferencedEntitiesDataUpdaterTest extends MediaWikiIntegrationTestCase {

	public function addDBData() {
		foreach ( [ 'P1', 'Q1', 'Q20', 'Q21', 'Q22' ] as $pageName ) {
			if ( $pageName[0] === 'P' ) {
				$entityType = 'property';
				$text = '{ "type": "property", "datatype": "string", "id": "P1" }';
			} else {
				$entityType = 'item';
				$text = '{ "type": "item", "id": "Q1" }';
			}

			$this->insertPage( $pageName, $text, $this->getEntityNamespace( $entityType ) );
		}
	}

	/**
	 * @param EntityId $entity
	 * @param EntityId[] $extractedEntities
	 *
	 * @return ReferencedEntitiesDataUpdater
	 */
	private function newInstance( EntityDocument $entity, array $extractedEntities ) {
		$mockEntityIdExtractor = $this->createMock( EntityReferenceExtractor::class );
		$mockEntityIdExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $entity )
			->willReturn( $extractedEntities );

		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->exactly( count( $extractedEntities ) ) )
			->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				$namespace = $this->getEntityNamespace( $id->getEntityType() );
				return Title::makeTitle( $namespace, $id->getSerialization() );
			} );

		return new ReferencedEntitiesDataUpdater(
			$mockEntityIdExtractor,
			$entityTitleLookup,
			$this->getServiceContainer()->getLinkBatchFactory()
		);
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testUpdateParserOutput(
		array $expectedEntityIds
	) {
		$item = new Item();
		$actual = [];

		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->expects( $this->exactly( count( $expectedEntityIds ) ) )
			->method( 'addLink' )
			->willReturnCallback( function( Title $title ) use ( &$actual ) {
				$actual[] = $title->getText();
			} );

		$instance = $this->newInstance( $item, $expectedEntityIds );

		$instance->updateParserOutput( $parserOutput, $item );
		$expectedEntityIdStrings = array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $expectedEntityIds );
		$this->assertArrayEquals( $expectedEntityIdStrings, $actual );
	}

	public function entityIdProvider() {
		return [
			[ [] ],
			[ [ new NumericPropertyId( 'P1' ), new ItemId( 'Q1' ) ] ],
			[ [ new ItemId( 'Q1' ) ] ],
			[ [ new NumericPropertyId( 'P1' ), new ItemId( 'Q1' ) ] ],
			[ [
				new NumericPropertyId( 'P1' ),
				new ItemId( 'Q20' ),
				new ItemId( 'Q21' ),
				new ItemId( 'Q22' ),
			] ],
		];
	}

	private function getEntityNamespace( string $entityType ): ?int {
		$entityNamespaceLookup = WikibaseRepo::getEntityNamespaceLookup();

		return $entityNamespaceLookup->getEntityNamespace( $entityType );
	}

}
