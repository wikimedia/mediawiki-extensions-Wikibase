<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use MediaWikiTestCase;
use ParserOutput;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractor;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class ReferencedEntitiesDataUpdaterTest extends MediaWikiTestCase {

	const UNIT_PREFIX = 'unit:';

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
		$mockEntityIdExtractor = $this->getMock( EntityReferenceExtractor::class );
		$mockEntityIdExtractor->expects( $this->once() )
			->method( 'extractEntityIds' )
			->with( $entity )
			->willReturn( $extractedEntities );

		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->exactly( count( $extractedEntities ) ) )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				$namespace = $this->getEntityNamespace( $id->getEntityType() );
				return Title::makeTitle( $namespace, $id->getSerialization() );
			} ) );

		return new ReferencedEntitiesDataUpdater( $mockEntityIdExtractor, $entityTitleLookup );
	}

	/**
	 * @dataProvider entityIdProvider
	 */
	public function testUpdateParserOutput(
		array $expectedEntityIds
	) {
		$item = new Item();
		$actual = [];

		$parserOutput = $this->getMockBuilder( ParserOutput::class )
			->disableOriginalConstructor()
			->getMock();
		$parserOutput->expects( $this->exactly( count( $expectedEntityIds ) ) )
			->method( 'addLink' )
			->will( $this->returnCallback( function( Title $title ) use ( &$actual ) {
				$actual[] = $title->getText();
			} ) );

		$instance = $this->newInstance( $item, $expectedEntityIds );

		$instance->processEntity( $item );

		$instance->updateParserOutput( $parserOutput );
		$expectedEntityIdStrings = array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $expectedEntityIds );
		$this->assertArrayEquals( $expectedEntityIdStrings, $actual );
	}

	public function entityIdProvider() {
		return [
			[ [] ],
			[ [ new PropertyId( 'P1' ), new ItemId( 'Q1' ) ] ],
			[ [ new ItemId( 'Q1' ) ] ],
			[ [ new PropertyId( 'P1' ), new ItemId( 'Q1' ) ] ],
			[ [
				new PropertyId( 'P1' ),
				new ItemId( 'Q20' ),
				new ItemId( 'Q21' ),
				new ItemId( 'Q22' ),
			] ],
		];
	}

	/**
	 * @param string $entityType
	 * @return int|null
	 */
	private function getEntityNamespace( $entityType ) {
		$entityNamespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

		return $entityNamespaceLookup->getEntityNamespace( $entityType );
	}

}
