<?php

namespace Wikibase\Repo\Tests\Content;

use MediaWiki\Interwiki\InterwikiLookup;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Content\ContentHandlerEntityTitleLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Content\PropertyContent;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Content\ContentHandlerEntityTitleLookup
 *
 * @group Wikibase
 * @group WikibaseEntity
 * @group WikibaseContent
 *
 * @group Database
 *        ^--- just because we use the Title class
 *
 * @license GPL-2.0-or-later
 */
class ContentHandlerEntityTitleLookupTest extends MediaWikiIntegrationTestCase {
	private function getItemSource() {
		return new DatabaseEntitySource(
			'itemwiki',
			'itemdb',
			[ 'item' => [ 'namespaceId' => 5000, 'slot' => SlotRecord::MAIN ] ],
			'',
			'',
			'',
			''
		);
	}

	protected function newFactory() {
		return new EntityContentFactory(
			[
				'item' => ItemContent::CONTENT_MODEL_ID,
				'property' => PropertyContent::CONTENT_MODEL_ID,
			],
			[
				'item' => function() {
					return WikibaseRepo::getItemHandler();
				},
				'property' => function() {
					return WikibaseRepo::getPropertyHandler();
				},
			]
		);
	}

	protected function newEntityTitleLookup() {
		$itemSource = $this->getItemSource();
		$propertySource = new DatabaseEntitySource(
			'propertywiki',
			'propertydb',
			[ 'property' => [ 'namespaceId' => 6000, 'slot' => SlotRecord::MAIN ] ],
			'',
			'p',
			'p',
			'propertywiki'
		);

		return new ContentHandlerEntityTitleLookup(
			$this->newFactory(),
			new EntitySourceDefinitions( [ $itemSource, $propertySource ], new SubEntityTypesMapper( [] ) ),
			$itemSource,
			MediaWikiServices::getInstance()->getInterwikiLookup()
		);
	}

	public function testGetTitleForId() {
		$factory = $this->newFactory();
		$entityTitleLookup = $this->newEntityTitleLookup();

		$id = new NumericPropertyId( 'P42' );
		$title = $entityTitleLookup->getTitleForId( $id );

		$this->assertEquals( 'P42', $title->getText() );

		$expectedNs = $factory->getNamespaceForType( $id->getEntityType() );
		$this->assertEquals( $expectedNs, $title->getNamespace() );
	}

	public function testGetTitleForId_nonLocalEntity() {
		$lookup = $this->createMock( InterwikiLookup::class );
		$lookup->method( 'isValidInterwiki' )
			->willReturn( true );
		$this->setService( 'InterwikiLookup', $lookup );

		$entityTitleLookup = $this->newEntityTitleLookup();
		$title = $entityTitleLookup->getTitleForId( new NumericPropertyId( 'P42' ) );
		$this->assertSame( 'propertywiki:Special:EntityPage/P42', $title->getFullText() );
	}

	public function testGetTitlesForIds_singleId() {
		$factory = $this->newFactory();
		$entityTitleLookup = $this->newEntityTitleLookup();

		$id = new NumericPropertyId( 'P42' );
		$titles = $entityTitleLookup->getTitlesForIds( [ $id ] );

		$this->assertEquals( 'P42', $titles['P42']->getText() );

		$expectedNs = $factory->getNamespaceForType( $id->getEntityType() );
		$this->assertEquals( $expectedNs, $titles['P42']->getNamespace() );
	}

	public function testGetTitlesForIds_nonLocalEntity() {
		$lookup = $this->createMock( InterwikiLookup::class );
		$lookup->method( 'isValidInterwiki' )
			->willReturn( true );
		$this->setService( 'InterwikiLookup', $lookup );

		$entityTitleLookup = $this->newEntityTitleLookup();
		$titles = $entityTitleLookup->getTitlesForIds( [ new NumericPropertyId( 'P42' ) ] );
		$this->assertSame(
			'propertywiki:Special:EntityPage/P42',
			$titles['P42']->getFullText()
		);
	}

	public function testGetTitlesForIds_multipleIdenticalIds() {
		$entityTitleLookup = $this->newEntityTitleLookup();

		$id = new NumericPropertyId( 'P42' );
		$titles = $entityTitleLookup->getTitlesForIds( [ $id, $id ] );

		$this->assertCount( 1, $titles );
		$this->assertEquals( 'P42', $titles['P42']->getText() );
	}

	public function testGetTitlesForIds_multipleDifferentIds() {
		$entityTitleLookup = $this->newEntityTitleLookup();

		$titles = $entityTitleLookup->getTitlesForIds( [
			new NumericPropertyId( 'P42' ),
			new NumericPropertyId( 'P43' ),
			new ItemId( 'Q42' ),
			new ItemId( 'Q43' ),
		] );

		$this->assertCount( 4, $titles );
		$this->assertEquals( 'P42', $titles['P42']->getText() );
		$this->assertEquals( 'P43', $titles['P43']->getText() );
		$this->assertEquals( 'Q42', $titles['Q42']->getText() );
		$this->assertEquals( 'Q43', $titles['Q43']->getText() );
	}

	public function testGetTitlesForIds_emptyArray() {
		$entityTitleLookup = $this->newEntityTitleLookup();

		$titles = $entityTitleLookup->getTitlesForIds( [] );

		$this->assertSame( [], $titles );
	}
}
