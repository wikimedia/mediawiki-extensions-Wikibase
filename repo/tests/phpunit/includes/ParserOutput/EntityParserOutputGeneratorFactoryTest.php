<?php

namespace Wikibase\Repo\Tests\ParserOutput;

use Language;
use NullStatsdDataFactory;
use RepoGroup;
use Serializers\Serializer;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactoryTest extends \MediaWikiTestCase {

	public function testGetEntityParserOutputGenerator() {
		$parserOutputGeneratorFactory = $this->getEntityParserOutputGeneratorFactory();

		$instance = $parserOutputGeneratorFactory->getEntityParserOutputGenerator( Language::factory( 'en' ) );

		$this->assertInstanceOf( EntityParserOutputGenerator::class, $instance );
	}

	private function getEntityParserOutputGeneratorFactory() {
		return new EntityParserOutputGeneratorFactory(
			$this->getMockBuilder( DispatchingEntityViewFactory::class )
				->disableOriginalConstructor()->getMock(),
			$this->createMock( DispatchingEntityMetaTagsCreatorFactory::class ),
			$this->createMock( EntityTitleLookup::class ),
			new LanguageFallbackChainFactory(),
			$this->getMockBuilder( TemplateFactory::class )
				->disableOriginalConstructor()->getMock(),
			$this->createMock( EntityDataFormatProvider::class ),
			new InMemoryDataTypeLookup(),
			$this->createMock( Serializer::class ),
			$this->getMockBuilder( EntityReferenceExtractorDelegator::class )
				->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder( CachingKartographerEmbeddingHandler::class )
				->disableOriginalConstructor()->getMock(),
			new NullStatsdDataFactory(),
			$this->createMock( RepoGroup::class )
		);
	}

}
