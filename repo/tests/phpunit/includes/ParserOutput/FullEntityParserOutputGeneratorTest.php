<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\StringValue;
use Language;
use MediaWikiIntegrationTestCase;
use Psr\SimpleCache\CacheInterface;
use RepoGroup;
use SpecialPage;
use Title;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\LanguageFallbackChain;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\CompositeStatementDataUpdater;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\ExternalLinksDataUpdater;
use Wikibase\Repo\ParserOutput\FullEntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdater;
use Wikibase\Repo\ParserOutput\ItemParserOutputUpdater;
use Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\View\EntityDocumentView;
use Wikibase\View\EntityMetaTagsCreator;
use Wikibase\View\EntityView;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
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
class FullEntityParserOutputGeneratorTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var DispatchingEntityViewFactory
	 */
	private $entityViewFactory;

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
				[
					new ItemId( 'Q42' ),
					new ItemId( 'Q35' ),
					new PropertyId( 'P42' ),
					new PropertyId( 'P10' )
				],
			],
			[ new Item(), null, [], [], [] ]
		];
	}

	/**
	 * EntityDocument $entity
	 * string|null $titleText
	 * string[] $externalLinks
	 * string[] $images
	 * EntityId[] $referencedEntities
	 *
	 * @dataProvider provideTestGetParserOutput
	 */
	public function testGetParserOutput(
		EntityDocument $entity,
		$titleText,
		array $externalLinks,
		array $images,
		array $referencedEntities
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

		$this->assertArrayEquals(
			$referencedEntities,
			$parserOutput->getExtensionData( 'referenced-entities' )
		);

		$alternateLinks = null;
		if ( $entity->getId() ) {
			$jsonHref = SpecialPage::getTitleFor( 'EntityData', $entity->getId()->getSerialization() . '.json' )->getCanonicalURL();
			$ntHref = SpecialPage::getTitleFor( 'EntityData', $entity->getId()->getSerialization() . '.nt' )->getCanonicalURL();
			$alternateLinks = [
				[
					'rel' => 'alternate',
					'href' => $jsonHref,
					'type' => 'application/json'
				],
				[
					'rel' => 'alternate',
					'href' => $ntHref,
					'type' => 'application/n-triples'
				]
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

		$this->assertSame( '', $parserOutput->getText() );
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
				$entityTitleLookup
			)
		];

		$cache = $this->createMock( CacheInterface::class );
		$cache->method( 'get' )
			->willReturn( false );

		return new FullEntityParserOutputGenerator(
			$this->entityViewFactory,
			$this->getEntityMetaTagsFactory( $title, $description ),
			$this->getConfigBuilderMock(),
			$entityTitleLookup,
			$this->newLanguageFallbackChain(),
			TemplateFactory::getDefaultInstance(),
			$this->createMock( LocalizedTextProvider::class ),
			$entityDataFormatProvider,
			$dataUpdaters,
			Language::factory( 'en' )
		);
	}

	/**
	 * @return LanguageFallbackChain
	 */
	private function newLanguageFallbackChain() {
		$fallbackChain = $this->getMockBuilder( LanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();

		$fallbackChain->expects( $this->any() )
			->method( 'extractPreferredValue' )
			->will( $this->returnCallback( function( $labels ) {
				if ( array_key_exists( 'en', $labels ) ) {
					return [
						'value' => $labels['en'],
						'language' => 'en',
						'source' => 'en'
					];
				}

				return null;
			} ) );

		$fallbackChain->method( 'getFetchLanguageCodes' )
			->willReturn( [ 'en' ] );

		return $fallbackChain;
	}

	private function newItem() {
		$item = new Item( new ItemId( 'Q7799929' ) );

		$item->setLabel( 'en', 'kitten item' );

		$statements = $item->getStatements();

		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'http://an.url.com' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 42, new StringValue( 'https://another.url.org' ) ) );

		$statements->addNewStatement( new PropertyValueSnak( 10, new StringValue( 'File:This is a file.pdf' ) ) );
		$statements->addNewStatement( new PropertyValueSnak( 10, new StringValue( 'File:Selfie.jpg' ) ) );

		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'kitten', [ new ItemId( 'Q42' ) ] );
		$item->getSiteLinkList()->addNewSiteLink( 'dewiki', 'meow', [ new ItemId( 'Q42' ), new ItemId( 'Q35' ) ] );

		return $item;
	}

	/**
	 * @param bool $createView
	 *
	 * @return DispatchingEntityViewFactory
	 */
	private function mockEntityViewFactory( $createView ) {
		$entityViewFactory = $this->getMockBuilder( DispatchingEntityViewFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$entityViewFactory->expects( $createView ? $this->once() : $this->never() )
			->method( 'newEntityView' )
			->will( $this->returnValue( $this->getEntityView() ) );

		return $entityViewFactory;
	}

	/**
	 * @return EntityDocumentView
	 */
	private function getEntityView() {
		$entityView = $this->getMockBuilder( EntityDocumentView::class )
			->setMethods( [
				'getTitleHtml',
				'getContent'
			] )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$entityView->expects( $this->any() )
			->method( 'getTitleHtml' )
			->will( $this->returnValue( '<TITLE>' ) );

		$viewContent = new ViewContent(
			'<HTML>',
			[]
		);

		$entityView->expects( $this->any() )
			->method( 'getContent' )
			->will( $this->returnValue( $viewContent ) );

		return $entityView;
	}

	/**
	 * @return DispatchingEntityMetaTagsCreatorFactory
	 */
	private function getEntityMetaTagsFactory( $title = null, $description = null ) {
		$entityMetaTagsCreatorFactory = $this->createMock( DispatchingEntityMetaTagsCreatorFactory::class );

		$entityMetaTagsCreatorFactory
			->method( 'newEntityMetaTags' )
			->will( $this->returnValue( $this->getMetaTags( $title, $description ) ) );

		return $entityMetaTagsCreatorFactory;
	}

	/**
	 * @return EntityMetaTags
	 */
	private function getMetaTags( $title, $description ) {
		$entityMetaTagsCreator = $this->getMockBuilder( EntityMetaTagsCreator::class )
			->setMethods( [
				'getMetaTags',
			] )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$tags = [];

		$tags[ 'title' ] = $title;

		if ( $description !== null ) {
			$tags[ 'description' ] = $description;
		}

		$entityMetaTagsCreator->expects( $this->any() )
			->method( 'getMetaTags' )
			->will( $this->returnValue( $tags ) );

		return $entityMetaTagsCreator;
	}

	/**
	 * @return ParserOutputJsConfigBuilder
	 */
	private function getConfigBuilderMock() {
		$configBuilder = $this->getMockBuilder( ParserOutputJsConfigBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$configBuilder->expects( $this->any() )
			->method( 'build' )
			->will( $this->returnValue( [ '<JS>' ] ) );

		return $configBuilder;
	}

	/**
	 * @return EntityTitleLookup
	 */
	private function getEntityTitleLookupMock() {
		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );

		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::makeTitle(
					NS_MAIN,
					$id->getEntityType() . ':' . $id->getSerialization()
				);
			} ) );

		return $entityTitleLookup;
	}

	private function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P42' ), 'url' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P10' ), 'commonsMedia' );

		return $dataTypeLookup;
	}

	private function newEntityReferenceExtractor() {
		return new EntityReferenceExtractorDelegator( [
			'item' => function() {
				return new EntityReferenceExtractorCollection( [
					new SiteLinkBadgeItemReferenceExtractor(),
					new StatementEntityReferenceExtractor(
						$this->getMockBuilder( SuffixEntityIdParser::class )
							->disableOriginalConstructor()
							->getMock()
					)
				] );
			}
		], $this->getMockBuilder( StatementEntityReferenceExtractor::class )
			->disableOriginalConstructor()
			->getMock() );
	}

}
