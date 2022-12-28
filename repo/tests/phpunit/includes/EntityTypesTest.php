<?php

namespace Wikibase\Repo\Tests;

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\View\EntityDocumentView;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @coversNothing
 */
class EntityTypesTest extends \PHPUnit\Framework\TestCase {

	private function getRegistry() {
		return require __DIR__ . '/../../../WikibaseRepo.entitytypes.php';
	}

	public function provideEntityTypes() {
		return array_map(
			function( $entityType ) {
				return [ $entityType ];
			},
			array_keys( $this->getRegistry() )
		);
	}

	public function testKnownEntityTypesSupported() {
		$entityTypes = $this->provideEntityTypes();

		$this->assertContains( [ 'item' ], $entityTypes );
		$this->assertContains( [ 'property' ], $entityTypes );
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testViewFactoryCallback( $entityType ) {
		$registry = $this->getRegistry();

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( EntityTypeDefinitions::VIEW_FACTORY_CALLBACK, $registry[$entityType] );

		$callback = $registry[$entityType][EntityTypeDefinitions::VIEW_FACTORY_CALLBACK];

		$this->assertIsCallable( $callback );

		$entityView = call_user_func(
			$callback,
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' ),
			new TermLanguageFallbackChain( [], $this->createStub( ContentLanguages::class ) ),
			new Item( new ItemId( 'Q123' ) )
		);

		$this->assertInstanceOf( EntityDocumentView::class, $entityView );
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testContentModelId( $entityType ) {
		$registry = $this->getRegistry();

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( EntityTypeDefinitions::CONTENT_MODEL_ID, $registry[$entityType] );
		$this->assertSame( 'wikibase-' . $entityType, $registry[$entityType][EntityTypeDefinitions::CONTENT_MODEL_ID] );
	}

	/**
	 * @dataProvider provideEntityTypes
	 */
	public function testContentHandlerFactoryCallback( $entityType ) {
		$registry = $this->getRegistry();

		$this->assertArrayHasKey( $entityType, $registry );
		$this->assertArrayHasKey( EntityTypeDefinitions::CONTENT_HANDLER_FACTORY_CALLBACK, $registry[$entityType] );

		$callback = $registry[$entityType][EntityTypeDefinitions::CONTENT_HANDLER_FACTORY_CALLBACK];

		$this->assertIsCallable( $callback );

		/** @var EntityHandler $entityHandler */
		$entityHandler = call_user_func( $callback );

		$this->assertInstanceOf( EntityHandler::class, $entityHandler );
		$this->assertSame( $entityType, $entityHandler->getEntityType() );
	}

}
