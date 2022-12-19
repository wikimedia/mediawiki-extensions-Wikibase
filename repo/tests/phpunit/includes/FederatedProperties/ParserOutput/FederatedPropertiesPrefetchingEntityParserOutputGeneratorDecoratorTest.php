<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Tests\FederatedProperties\ParserOutput;

use Psr\SimpleCache\CacheInterface;
use RepoGroup;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Repo\FederatedProperties\ApiEntityLookup;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\ParserOutput\CompositeStatementDataUpdater;
use Wikibase\Repo\ParserOutput\ExternalLinksDataUpdater;
use Wikibase\Repo\ParserOutput\FullEntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\ImageLinksDataUpdater;
use Wikibase\Repo\ParserOutput\ItemParserOutputUpdater;
use Wikibase\Repo\ParserOutput\ReferencedEntitiesDataUpdater;
use Wikibase\Repo\Tests\ParserOutput\EntityParserOutputGeneratorTestBase;

/**
 * @covers \Wikibase\Repo\FederatedProperties\FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecoratorTest extends EntityParserOutputGeneratorTestBase {

	public function testShouldPrefetchFederatedProperties() {
		$labelLanguage = 'en';

		$fedPropId1 = new FederatedPropertyId( 'http://wikidata.org/entity/P123', 'P123' );
		$fedPropId2 = new FederatedPropertyId( 'http://wikidata.org/entity/P321', 'P321' );

		$item = new Item( new ItemId( 'Q7799929' ) );
		$item->setLabel( $labelLanguage, 'kitten item' );

		$statementWithReference = new Statement( new PropertyNoValueSnak( $fedPropId1 ) );
		$statementWithReference->addNewReference( new PropertyNoValueSnak( 4 ) );

		$item->getStatements()->addStatement( $statementWithReference );
		$item->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( 2 ) ) );
		$item->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( $fedPropId2 ) ) );
		$item->getStatements()->addStatement( new Statement( new PropertyNoValueSnak( $fedPropId2 ) ) );

		$expectedIds = [ $fedPropId1, $fedPropId2 ];

		$this->entityViewFactory = $this->mockEntityViewFactory( false );

		$apiEntityLookup = $this->createMock( ApiEntityLookup::class );
		$apiEntityLookup->expects( $this->once() )
			->method( 'fetchEntities' )
			->willReturnCallback( $this->getPrefetchCallback(
				$expectedIds
			) );

		$innerPog = $this->getFullGeneratorMock();
		$entityParserOutputGenerator = $this->newEntityParserOutputGenerator( $apiEntityLookup, $innerPog );

		$entityParserOutputGenerator->getParserOutput( new EntityRevision( $item, 4711 ), false );
	}

	protected function getPrefetchCallback( $expectedIds ) {
		return function (
			array $entityIds,
			array $termTypes = null,
			array $languageCodes = null
		) use (
			$expectedIds
		) {
			$expectedIdStrings = array_map( function( EntityId $id ) {
				return $id->getSerialization();
			}, $expectedIds );
			$entityIdStrings = array_map( function( EntityId $id ) {
				return $id->getSerialization();
			}, $entityIds );

			sort( $expectedIdStrings );
			sort( $entityIdStrings );

			$this->assertEquals( $expectedIdStrings, $entityIdStrings );
		};
	}

	private function newEntityParserOutputGenerator( $apiEntityLookup, $fullGenerator ) {
		return new FederatedPropertiesPrefetchingEntityParserOutputGeneratorDecorator(
			$fullGenerator,
			$apiEntityLookup
		);
	}

	private function getFullGeneratorMock( $dataUpdaters = null, $language = 'en', $title = null, $description = null ) {
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

		return new FullEntityParserOutputGenerator(
			$this->entityViewFactory,
			$this->getEntityMetaTagsFactory( $title, $description ),
			$this->getConfigBuilderMock(),
			$this->newLanguageFallbackChain(),
			$entityDataFormatProvider,
			$dataUpdaters,
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( $language )
		);
	}
}
