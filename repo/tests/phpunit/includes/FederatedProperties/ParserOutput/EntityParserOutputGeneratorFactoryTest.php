<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\ParserOutput;

use MediaWiki\Cache\LinkBatchFactory;
use NullStatsdDataFactory;
use RepoGroup;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesUiEntityParserOutputGeneratorDecorator;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\Tests\FederatedProperties\FederatedPropertiesTestCase;

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
		$parserOutputGeneratorFactory = $this->getEntityParserOutputGeneratorFactory();
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$instance = $parserOutputGeneratorFactory->getEntityParserOutputGenerator( $lang );

		$this->assertInstanceOf( FederatedPropertiesUiEntityParserOutputGeneratorDecorator::class, $instance );
	}

	private function getEntityParserOutputGeneratorFactory() {
		return new EntityParserOutputGeneratorFactory(
			$this->createMock( DispatchingEntityViewFactory::class ),
			$this->createMock( DispatchingEntityMetaTagsCreatorFactory::class ),
			$this->createMock( EntityTitleLookup::class ),
			new LanguageFallbackChainFactory(),
			$this->createMock( EntityDataFormatProvider::class ),
			new InMemoryDataTypeLookup(),
			$this->createMock( EntityReferenceExtractorDelegator::class ),
			$this->createMock( CachingKartographerEmbeddingHandler::class ),
			new NullStatsdDataFactory(),
			$this->createMock( RepoGroup::class ),
			$this->createMock( LinkBatchFactory::class )
		);
	}

}
