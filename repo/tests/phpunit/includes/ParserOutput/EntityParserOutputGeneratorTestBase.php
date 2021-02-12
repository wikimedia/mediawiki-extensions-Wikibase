<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\ParserOutput;

use DataValues\StringValue;
use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\ParserOutputJsConfigBuilder;
use Wikibase\View\EntityDocumentView;
use Wikibase\View\EntityMetaTagsCreator;
use Wikibase\View\ViewContent;

/**
 * BaseClass with helper methods for required
 * services for mocking EntityParserOutputGenerator
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class EntityParserOutputGeneratorTestBase extends MediaWikiIntegrationTestCase {

	/**
	 * @var DispatchingEntityViewFactory
	 */
	protected $entityViewFactory;

	/**
	 * @return TermLanguageFallbackChain
	 */
	protected function newLanguageFallbackChain() {
		$fallbackChain = $this->getMockBuilder( TermLanguageFallbackChain::class )
			->disableOriginalConstructor()
			->getMock();

		$fallbackChain->method( 'extractPreferredValue' )
			->willReturnCallback( function( $labels ) {
				if ( array_key_exists( 'en', $labels ) ) {
					return [
						'value' => $labels['en'],
						'language' => 'en',
						'source' => 'en'
					];
				}

				return null;
			} );

		$fallbackChain->method( 'getFetchLanguageCodes' )
			->willReturn( [ 'en' ] );

		return $fallbackChain;
	}

	protected function newItem() {
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
	public function mockEntityViewFactory( $createView ) {
		$entityViewFactory = $this->getMockBuilder( DispatchingEntityViewFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$entityViewFactory->expects( $createView ? $this->once() : $this->never() )
			->method( 'newEntityView' )
			->willReturn( $this->getEntityView() );

		return $entityViewFactory;
	}

	/**
	 * @return EntityDocumentView
	 */
	protected function getEntityView() {
		$entityView = $this->getMockBuilder( EntityDocumentView::class )
			->setMethods( [
				'getTitleHtml',
				'getContent'
			] )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$entityView->method( 'getTitleHtml' )
			->willReturn( '<TITLE>' );

		$viewContent = new ViewContent(
			'<HTML>',
			[]
		);

		$entityView->method( 'getContent' )
			->willReturn( $viewContent );

		return $entityView;
	}

	/**
	 * @return DispatchingEntityMetaTagsCreatorFactory
	 */
	protected function getEntityMetaTagsFactory( $title = null, $description = null ) {
		$entityMetaTagsCreatorFactory = $this->createMock( DispatchingEntityMetaTagsCreatorFactory::class );

		$entityMetaTagsCreatorFactory
			->method( 'newEntityMetaTags' )
			->willReturn( $this->getMetaTags( $title, $description ) );

		return $entityMetaTagsCreatorFactory;
	}

	/**
	 * @return EntityMetaTags
	 */
	protected function getMetaTags( $title, $description ) {
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

		$entityMetaTagsCreator->method( 'getMetaTags' )
			->willReturn( $tags );

		return $entityMetaTagsCreator;
	}

	/**
	 * @return ParserOutputJsConfigBuilder
	 */
	protected function getConfigBuilderMock() {
		$configBuilder = $this->getMockBuilder( ParserOutputJsConfigBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$configBuilder->method( 'build' )
			->willReturn( [ '<JS>' ] );

		return $configBuilder;
	}

	/**
	 * @return EntityTitleLookup
	 */
	protected function getEntityTitleLookupMock() {
		$entityTitleLookup = $this->createMock( EntityTitleLookup::class );

		$entityTitleLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				return Title::makeTitle(
					NS_MAIN,
					$id->getEntityType() . ':' . $id->getSerialization()
				);
			} );

		return $entityTitleLookup;
	}

	protected function getPropertyDataTypeLookup() {
		$dataTypeLookup = new InMemoryDataTypeLookup();

		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P42' ), 'url' );
		$dataTypeLookup->setDataTypeForProperty( new PropertyId( 'P10' ), 'commonsMedia' );

		return $dataTypeLookup;
	}

	protected function newEntityReferenceExtractor() {
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
