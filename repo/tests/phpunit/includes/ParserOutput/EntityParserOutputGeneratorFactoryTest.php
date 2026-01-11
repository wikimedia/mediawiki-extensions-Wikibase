<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\ParserOutput;

use MediaWiki\FileRepo\RepoGroup;
use MediaWiki\Language\LanguageFactory;
use MediaWiki\Language\LanguageNameUtils;
use MediaWiki\Page\LinkBatchFactory;
use MediaWiki\Parser\ParserOptions;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\Hooks\WikibaseRepoOnParserOutputUpdaterConstructionHook;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikimedia\Stats\StatsFactory;

/**
 * @covers \Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityParserOutputGeneratorFactoryTest extends MediaWikiIntegrationTestCase {

	public function testGetEntityParserOutputGenerator() {
		$parserOutputGeneratorFactory = $this->getEntityParserOutputGeneratorFactory();

		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' );
		$parserOptions = ParserOptions::newFromAnon();
		$parserOptions->setUserLang( $lang );
		$instance = $parserOutputGeneratorFactory->getEntityParserOutputGeneratorForParserOptions( $parserOptions );

		$this->assertInstanceOf( EntityParserOutputGenerator::class, $instance );
	}

	private function getEntityParserOutputGeneratorFactory() {
		return new EntityParserOutputGeneratorFactory(
			$this->createMock( DispatchingEntityViewFactory::class ),
			$this->createMock( DispatchingEntityMetaTagsCreatorFactory::class ),
			$this->createMock( EntityTitleLookup::class ),
			$this->createMock( LanguageFactory::class ),
			new LanguageFallbackChainFactory(),
			$this->createMock( LanguageNameUtils::class ),
			$this->createMock( EntityDataFormatProvider::class ),
			new InMemoryDataTypeLookup(),
			$this->createMock( EntityReferenceExtractorDelegator::class ),
			$this->createMock( CachingKartographerEmbeddingHandler::class ),
			StatsFactory::newNull(),
			$this->createMock( RepoGroup::class ),
			$this->createMock( LinkBatchFactory::class ),
			$this->createMock( WikibaseRepoOnParserOutputUpdaterConstructionHook::class ),
			false
		);
	}

}
