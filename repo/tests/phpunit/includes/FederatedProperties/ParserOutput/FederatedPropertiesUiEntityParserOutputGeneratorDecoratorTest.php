<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\ParserOutput;

use Psr\SimpleCache\CacheInterface;
use RepoGroup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\FederatedProperties\ApiEntityLookup;
use Wikibase\Repo\FederatedProperties\ApiRequestExecutionException;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesError;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesUiEntityParserOutputGeneratorDecorator;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\CompositeStatementDataUpdater;
use Wikibase\Repo\ParserOutput\ExternalLinksDataUpdater;
use Wikibase\Repo\ParserOutput\FullEntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdater;
use Wikibase\Repo\ParserOutput\ItemParserOutputUpdater;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\Repo\Tests\ParserOutput\EntityParserOutputGeneratorTestBase;

/**
 * @covers \Wikibase\Repo\FederatedProperties\FederatedPropertiesUiEntityParserOutputGeneratorDecorator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesUiEntityParserOutputGeneratorDecoratorTest extends EntityParserOutputGeneratorTestBase {

	/**
	 * @dataProvider errorPageProvider
	 */
	public function testGetParserOutputHandlesFederatedApiException( $labelLanguage, $userLanguage ) {

		$item = new Item( new ItemId( 'Q7799929' ) );
		$item->setLabel( $labelLanguage, 'kitten item' );

		$updater = $this->createMock( ItemParserOutputUpdater::class );

		$this->entityViewFactory = $this->mockEntityViewFactory( false );

		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator(
			$this->newPrefetchingParserOutputGenerator( [ $updater ], $userLanguage ),
			$userLanguage
		);
		$updater->method( 'updateParserOutput' )
			->willThrowException( new ApiRequestExecutionException() );

		// T254888 Exception will be handled and show an error page.
		$this->expectException( FederatedPropertiesError::class );

		$entityParserOutputGenerator->getParserOutput( new EntityRevision( $item, 4711 ), false );
	}

	public function testParserOutputLoadModule() {
		$item = new Item( new ItemId( 'Q7799929' ) );
		$item->setLabel( 'en', 'kitten item' );
		$entityRevision = new EntityRevision( $item, 4711 );

		$updater = $this->createMock( ItemParserOutputUpdater::class );

		$this->entityViewFactory = $this->mockEntityViewFactory( true );

		$parserOutputGen = $this->newEntityParserOutputGenerator(
			$this->newPrefetchingParserOutputGenerator( [ $updater ], 'en' ),
			'en'
		);

		$parserOutput = $parserOutputGen->getParserOutput( $entityRevision );
		$resourceLoaderModules = $parserOutput->getModules();
		$this->assertContains( 'wikibase.federatedPropertiesLeavingSiteNotice', $resourceLoaderModules );
		$this->assertContains( 'wikibase.federatedPropertiesEditRequestFailureNotice', $resourceLoaderModules );
	}

	public function errorPageProvider() {
		return [
			[ 'en', 'en' ],
			[ 'de', 'en' ],
		];
	}

	private function newEntityParserOutputGenerator( $fullGenerator, $languageCode = 'en' ) {
		return new FederatedPropertiesUiEntityParserOutputGeneratorDecorator(
			$fullGenerator,
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( $languageCode )
		);
	}

	private function newPrefetchingParserOutputGenerator( $dataUpdaters = null, $language = 'en', $title = null, $description = null ) {
		$entityDataFormatProvider = new EntityDataFormatProvider();
		$entityDataFormatProvider->setAllowedFormats( [ 'json', 'ntriples' ] );

		$propertyDataTypeMatcher = new PropertyDataTypeMatcher( $this->getPropertyDataTypeLookup() );
		$repoGroup = $this->createMock( RepoGroup::class );

		$statementUpdater = new CompositeStatementDataUpdater(
			new ExternalLinksDataUpdater( $propertyDataTypeMatcher ),
			new ImageLinksDataUpdater( $propertyDataTypeMatcher, $repoGroup )
		);

		if ( $dataUpdaters === null ) {
			$dataUpdaters = [
				new ItemParserOutputUpdater( $statementUpdater ),
				new ReferencedEntitiesDataUpdater(
					$this->newEntityReferenceExtractor(),
					$this->getEntityTitleLookupMock(),
					$this->getServiceContainer()->getLinkBatchFactory()
				),
			];
		}

		$cache = $this->createMock( CacheInterface::class );
		$cache->method( 'get' )
			->willReturn( false );

		return new FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator(
			new FullEntityParserOutputGenerator(
				$this->entityViewFactory,
				$this->getEntityMetaTagsFactory( $title, $description ),
				$this->getConfigBuilderMock(),
				$this->newLanguageFallbackChain(),
				$entityDataFormatProvider,
				$dataUpdaters,
				$this->getServiceContainer()->getLanguageFactory()->getLanguage( $language )
			),
			$this->createStub( ApiEntityLookup::class )
		);
	}
}
