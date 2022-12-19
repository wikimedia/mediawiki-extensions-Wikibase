<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Psr\SimpleCache\CacheInterface;
use RepoGroup;
use SpecialPage;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\CompositeStatementDataUpdater;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\ExternalLinksDataUpdater;
use Wikibase\Repo\ParserOutput\FullEntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdater;
use Wikibase\Repo\ParserOutput\ItemParserOutputUpdater;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\View\EntityView;
use Wikibase\View\ViewContent;
use Wikibase\View\ViewPlaceHolderEmitter;

/**
 * @covers \Wikibase\Repo\ParserOutput\FullEntityParserOutputGenerator
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class FullEntityParserOutputGeneratorTest extends EntityParserOutputGeneratorTestBase {

	protected function setUp(): void {
		parent::setUp();

		$this->entityViewFactory = $this->mockEntityViewFactory( false );
	}

	public function provideTestGetParserOutput() {
		return [
			[
				$this->newItem(),
				'kitten item',
				[ 'http://an.url.com', 'https://another.url.org' ],
				[ 'File:This_is_a_file.pdf', 'File:Selfie.jpg' ],
			],
			[ new Item(), null, [], [] ],
		];
	}

	/**
	 * EntityDocument $entity
	 * string|null $titleText
	 * string[] $externalLinks
	 * string[] $images
	 *
	 * @dataProvider provideTestGetParserOutput
	 */
	public function testGetParserOutput(
		EntityDocument $entity,
		$titleText,
		array $externalLinks,
		array $images
	) {
		$this->entityViewFactory = $this->mockEntityViewFactory( true );
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator( $titleText );

		$entityRevision = new EntityRevision( $entity, 4711 );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $entityRevision );

		$this->assertSame( '<TITLE>', $parserOutput->getTitleText(), 'title text' );
		$this->assertSame( '<HTML>', $parserOutput->getText(), 'html text' );

		/**
		 * @see \Wikibase\Repo\Tests\ParserOutput\FullEntityParserOutputGeneratorIntegrationTest
		 * for tests concerning html view placeholder integration.
		 */

		$this->assertSame( [ '<JS>' ], $parserOutput->getJsConfigVars(), 'config vars' );

		$this->assertSame(
			[
				'title' => $titleText,
			],
			$parserOutput->getExtensionData( 'wikibase-meta-tags' )
		);

		$this->assertEquals(
			$externalLinks,
			array_keys( $parserOutput->getExternalLinks() ),
			'external links'
		);

		$this->assertEquals(
			$images,
			array_keys( $parserOutput->getImages() ),
			'images'
		);

		// TODO would be nice to test this, but ReferencedEntitiesDataUpdater uses LinkBatch which uses the database
//		$this->assertEquals(
//			[ 'item:Q42', 'item:Q35' ],
//			array_keys( $parserOutput->getLinks()[NS_MAIN] ),
//			'badges'
//		);

		$alternateLinks = null;
		if ( $entity->getId() ) {
			$jsonHref = SpecialPage::getTitleFor( 'EntityData', $entity->getId()->getSerialization() . '.json' )->getCanonicalURL();
			$ntHref = SpecialPage::getTitleFor( 'EntityData', $entity->getId()->getSerialization() . '.nt' )->getCanonicalURL();
			$alternateLinks = [
				[
					'rel' => 'alternate',
					'href' => $jsonHref,
					'type' => 'application/json',
				],
				[
					'rel' => 'alternate',
					'href' => $ntHref,
					'type' => 'application/n-triples',
				],
			];
		}

		$this->assertEquals(
			$alternateLinks,
			$parserOutput->getExtensionData( 'wikibase-alternate-links' ),
			'alternate links (extension data)'
		);

		$resourceLoaderModules = $parserOutput->getModules();
		$this->assertContains( 'wikibase.entityPage.entityLoaded', $resourceLoaderModules );
		$this->assertContains( 'wikibase.ui.entityViewInit', $resourceLoaderModules );
	}

	public function testGetParserOutput_dontGenerateHtml() {
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator();

		$item = $this->newItem();

		$entityRevision = new EntityRevision( $item, 4711 );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $entityRevision, false );

		$this->assertFalse( $parserOutput->hasText() );
		// ParserOutput without HTML must not end up in the cache.
		$this->assertFalse( $parserOutput->isCacheable() );
	}

	public function testGivenErroneousViewPlaceholderValue_parserOutputBecomesUncacheable() {
		$viewContent = $this->createMock( ViewContent::class );
		$viewContent->expects( $this->once() )
			->method( 'getPlaceholders' )
			->willReturn( [ 'placeholder-name' => ViewPlaceHolderEmitter::ERRONEOUS_PLACEHOLDER_VALUE ] );

		$entityView = $this->createMock( EntityView::class );
		$entityView->expects( $this->once() )
			->method( 'getContent' )
			->willReturn( $viewContent );

		$this->entityViewFactory = $this->createMock( DispatchingEntityViewFactory::class );
		$this->entityViewFactory->expects( $this->once() )
			->method( 'newEntityView' )
			->willReturn( $entityView );

		$entityRevision = new EntityRevision( new Item( new ItemId( 'Q42' ) ), 4711 );

		$parserOutput = $this->newEntityParserOutputGenerator()
			->getParserOutput( $entityRevision, true );

		$this->assertFalse( $parserOutput->isCacheable() );
	}

	public function testTitleText_ItemHasNoLabel() {
		$this->entityViewFactory = $this->mockEntityViewFactory( true );
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator( 'Q7799929', 'a kitten' );

		$item = new Item( new ItemId( 'Q7799929' ) );
		$item->setDescription( 'en', 'a kitten' );

		$entityRevision = new EntityRevision( $item, 4711 );

		$parserOutput = $entityParserOutputGenerator->getParserOutput( $entityRevision );

		$this->assertSame(
			[
				'title' => 'Q7799929',
				'description' => 'a kitten',
			],
			$parserOutput->getExtensionData( 'wikibase-meta-tags' )
		);
	}

	private function newEntityParserOutputGenerator( $title = null, $description = null ) {
		$entityDataFormatProvider = new EntityDataFormatProvider();
		$entityDataFormatProvider->setAllowedFormats( [ 'json', 'ntriples' ] );

		$entityTitleLookup = $this->getEntityTitleLookupMock();

		$propertyDataTypeMatcher = new PropertyDataTypeMatcher( $this->getPropertyDataTypeLookup() );
		$repoGroup = $this->createMock( RepoGroup::class );

		$statementUpdater = new CompositeStatementDataUpdater(
			new ExternalLinksDataUpdater( $propertyDataTypeMatcher ),
			new ImageLinksDataUpdater( $propertyDataTypeMatcher, $repoGroup )
		);

		$dataUpdaters = [
			new ItemParserOutputUpdater( $statementUpdater ),
			new ReferencedEntitiesDataUpdater(
				$this->newEntityReferenceExtractor(),
				$entityTitleLookup,
				$this->getServiceContainer()->getLinkBatchFactory()
			),
		];

		$cache = $this->createMock( CacheInterface::class );
		$cache->method( 'get' )
			->willReturn( false );

		return new FullEntityParserOutputGenerator(
			$this->entityViewFactory,
			$this->getEntityMetaTagsFactory( $title, $description ),
			$this->getConfigBuilderMock(),
			$this->newLanguageFallbackChain(),
			$entityDataFormatProvider,
			$dataUpdaters,
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
		);
	}
}
