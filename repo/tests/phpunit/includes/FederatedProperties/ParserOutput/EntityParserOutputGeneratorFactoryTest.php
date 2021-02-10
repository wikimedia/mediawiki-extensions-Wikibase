<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\ParserOutput;

use Language;
use NullStatsdDataFactory;
use RepoGroup;
use Serializers\Serializer;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesEntityParserOutputGenerator;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\Tests\FederatedProperties\FederatedPropertiesTestCase;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class EntityParserOutputGeneratorFactoryTest extends FederatedPropertiesTestCase {

	public function testGetFederatedPropertiesEntityParserOutputGenerator() {
		$this->setFederatedPropertiesEnabled();

		$parserOutputGeneratorFactory = $this->getEntityParserOutputGeneratorFactory();
		$instance = $parserOutputGeneratorFactory->getEntityParserOutputGenerator( Language::factory( 'en' ) );

		$this->assertInstanceOf( FederatedPropertiesEntityParserOutputGenerator::class, $instance );
	}

	private function getEntityParserOutputGeneratorFactory() {
		return new EntityParserOutputGeneratorFactory(
			$this->createMock( DispatchingEntityViewFactory::class ),
			$this->createMock( DispatchingEntityMetaTagsCreatorFactory::class ),
			$this->createMock( EntityTitleLookup::class ),
			new LanguageFallbackChainFactory(),
			$this->createMock( TemplateFactory::class ),
			$this->createMock( EntityDataFormatProvider::class ),
			new InMemoryDataTypeLookup(),
			$this->createMock( Serializer::class ),
			$this->createMock( EntityReferenceExtractorDelegator::class ),
			$this->createMock( CachingKartographerEmbeddingHandler::class ),
			new NullStatsdDataFactory(),
			$this->createMock( RepoGroup::class )
		);
	}

}
