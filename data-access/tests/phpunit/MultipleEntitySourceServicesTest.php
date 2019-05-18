<?php

namespace Wikibase\DataAccess\Tests;

use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\MultipleEntitySourceServices;
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\Interactors\TermSearchResult;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;
use Wikibase\TermIndexEntry;

/**
 * @covers \Wikibase\DataAccess\MultipleEntitySourceServices
 *
 * @group Wikibase
 * @group NotLegitUnitTest
 *
 * @license GPL-2.0-or-later
 */
class MultipleEntitySourceServicesTest extends \PHPUnit_Framework_TestCase {

	use \PHPUnit4And6Compat;

	public function testGetEntityRevisionLookupReturnsLookupThatReturnsExpectedRevisionData() {
		$itemRevisionData = 'item revision data';
		$itemRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$itemRevisionLookup->method( 'getEntityRevision' )
			->willReturn( $itemRevisionData );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getEntityRevisionLookup' )
			->willReturn( $itemRevisionLookup );

		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->method( 'getEntityRevisionLookup' )
			->willReturn( $this->newThrowingEntityRevisionLookup() );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$lookup = $services->getEntityRevisionLookup();

		$this->assertSame( $itemRevisionData, $lookup->getEntityRevision( new ItemId( 'Q123' ) ) );
	}

	private function newThrowingEntityRevisionLookup() {
		$propertyRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$propertyRevisionLookup->method( $this->anything() )
			->willThrowException( new \LogicException( 'This service should not be used' ) );
		return $propertyRevisionLookup;
	}

	public function testGetEntityInfoBuilderReturnsBuilderHandlingAllSourceEntities() {
		$itemId = 'Q200';
		$propertyId = 'P500';

		$itemInfoBuilder = $this->createMock( EntityInfoBuilder::class );
		$itemInfoBuilder->method( 'collectEntityInfo' )
			->willReturn( new EntityInfo( [ $itemId => 'item info' ] ) );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getEntityInfoBuilder' )
			->willReturn( $itemInfoBuilder );

		$propertyInfoBuilder = $this->createMock( EntityInfoBuilder::class );
		$propertyInfoBuilder->method( 'collectEntityInfo' )
			->willReturn( new EntityInfo( [ $propertyId => 'property info' ] ) );

		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->method( 'getEntityInfoBuilder' )
			->willReturn( $propertyInfoBuilder );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$infoBuilder = $services->getEntityInfoBuilder();
		$info = $infoBuilder->collectEntityInfo( [ new PropertyId( $propertyId ), new ItemId( $itemId ) ], [ 'en' ] );

		$this->assertEquals( [ $itemId => 'item info', $propertyId => 'property info' ], $info->asArray() );
	}

	public function testGetTermSearchInteractorFactoryGeneratesInteractorsReturningResultsForConfiguredSources() {
		$itemResult = new TermSearchResult( new Term( 'en', 'test' ), TermIndexEntry::TYPE_LABEL, new ItemId( 'Q123' ) );

		$itemInteractor = $this->createMock( TermSearchInteractor::class );
		$itemInteractor->method( 'searchForEntities' )
			->willReturn( $itemResult );

		$itemInteractorFactory = $this->createMock( TermSearchInteractorFactory::class );
		$itemInteractorFactory->method( 'newInteractor' )
			->willReturn( $itemInteractor );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getTermSearchInteractorFactory' )
			->willReturn( $itemInteractorFactory );

		$dummyInteractorFactory = $this->createMock( TermSearchInteractorFactory::class );
		$dummyInteractorFactory->method( 'newInteractor' )
			->willReturn( $this->createMock( TermSearchInteractor::class ) );

		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->method( 'getTermSearchInteractorFactory' )
			->willReturn( $dummyInteractorFactory );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$interactor = $services->getTermSearchInteractorFactory()->newInteractor( 'en' );

		$searchResult = $interactor->searchForEntities( 'test', 'en', 'item', [ TermIndexEntry::TYPE_LABEL ] );

		$this->assertEquals( $itemResult, $searchResult );
	}

	public function testGetPrefetchingTermLookupReturnsLookupBufferingDataOfSourceEntities() {
		$itemId = new ItemId( 'Q200' );
		$propertyId = new PropertyId( 'P500' );
		$fakeItemLabel = 'Q200 en label';
		$fakePropertyLabel = 'P500 en label';

		$itemLookup = new FakePrefetchingTermLookup();

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getPrefetchingTermLookup' )
			->willReturn( $itemLookup );

		$propertyLookup = new FakePrefetchingTermLookup();

		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->method( 'getPrefetchingTermLookup' )
			->willReturn( $propertyLookup );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$lookup = $services->getPrefetchingTermLookup();
		$lookup->prefetchTerms( [ $itemId, $propertyId ], [ 'label' ], [ 'en' ] );

		$this->assertEquals( $fakeItemLabel, $lookup->getPrefetchedTerm( $itemId, 'label', 'en' ) );
		$this->assertEquals( $fakePropertyLabel, $lookup->getPrefetchedTerm( $propertyId, 'label', 'en' ) );
	}

	public function testGetPrefetchingTermLookupReturnsLookupReturningTermsOfEntitiesConfiguredInSources() {
		$itemId = new ItemId( 'Q200' );
		$fakeItemLabel = 'Q200 en label';

		$itemLookup = new FakePrefetchingTermLookup();

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getPrefetchingTermLookup' )
			->willReturn( $itemLookup );

		$services = new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource( 'items', 'itemdb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '' )
			] ),
			new GenericServices( new EntityTypeDefinitions( [] ), [], [] ),
			[ 'items' => $itemServices ]
		);

		$lookup = $services->getPrefetchingTermLookup();

		$this->assertEquals( $fakeItemLabel, $lookup->getLabel( $itemId, 'en' ) );
	}

	public function testGetPrefetchingTermLookupReturnsLookupReturningNullWhenGivenEntitiesUnknownInSources() {
		$itemLookup = new FakePrefetchingTermLookup();

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getPrefetchingTermLookup' )
			->willReturn( $itemLookup );

		$services = new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource( 'items', 'itemdb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '' )
			] ),
			new GenericServices( new EntityTypeDefinitions( [] ), [], [] ),
			[ 'items' => $itemServices ]
		);

		$lookup = $services->getPrefetchingTermLookup();

		$this->assertNull( $lookup->getLabel( new PropertyId( 'P123' ), 'en' ) );
	}

	public function testGetEntityPrefetcherReturnsServiceBufferingDataOfSourceEntities() {
		$itemId = new ItemId( 'Q200' );
		$propertyId = new PropertyId( 'P500' );

		$itemPrefetcher = new EntityPrefetcherSpy();

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getEntityPrefetcher' )
			->willReturn( $itemPrefetcher );

		$propertyPrefetcher = new EntityPrefetcherSpy();

		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->method( 'getEntityPrefetcher' )
			->willReturn( $propertyPrefetcher );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$prefetcher = $services->getEntityPrefetcher();
		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$this->assertEquals(
			[ $itemId, $propertyId ],
			array_merge( $itemPrefetcher->getPrefetchedEntities(), $propertyPrefetcher->getPrefetchedEntities() )
		);
	}

	public function testGetEntityPrefetcherReturnsServiceThatDoesNotPrefetchEntitiesNotConfiguredInSources() {
		$itemId = new ItemId( 'Q200' );
		$propertyId = new PropertyId( 'P500' );

		$itemPrefetcher = new EntityPrefetcherSpy();

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->method( 'getEntityPrefetcher' )
			->willReturn( $itemPrefetcher );

		$services = new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource( 'items', 'itemdb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '' )
			] ),
			new GenericServices( new EntityTypeDefinitions( [] ), [], [] ),
			[ 'items' => $itemServices ]
		);

		$prefetcher = $services->getEntityPrefetcher();
		$prefetcher->prefetch( [ $itemId, $propertyId ] );

		$this->assertNotContains( [ $propertyId ], $itemPrefetcher->getPrefetchedEntities() );
	}

	public function testGetPropertyInfoLookupReturnsPropertyDataAccessingService() {
		$propertyId = new PropertyId( 'P6' );

		$propertyLookup = new MockPropertyInfoLookup();
		$propertyLookup->addPropertyInfo( $propertyId, [ PropertyInfoLookup::KEY_DATA_TYPE => 'string' ] );

		$sourceServices = $this->createMock( SingleEntitySourceServices::class );
		$sourceServices->method( 'getPropertyInfoLookup' )
			->willReturn( $propertyLookup );

		$services = $this->newMultipleEntitySourceServices( [ 'props' => $sourceServices ] );

		$lookup = $services->getPropertyInfoLookup();

		$this->assertEquals( [ 'type' => 'string' ], $lookup->getPropertyInfo( $propertyId ) );
	}

	/**
	 * @expectedException \LogicException
	 */
	public function testGivenNoSourceProvidingProperties_getPropertyInfoLookupThrowsException() {
		$services = new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource( 'items', 'itemdb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '' ),
			] ),
			new GenericServices( new EntityTypeDefinitions( [] ), [], [] ),
			[]
		);

		$services->getPropertyInfoLookup();
	}

	public function testEntityFromKnownSourceUpdated_entityUpdatedPassedToRelevantServiceContainer() {
		$itemRevision = new EntityRevision( new Item( new ItemId( 'Q1' ) ) );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'entityUpdated' )
			->with( $itemRevision );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'entityUpdated' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->entityUpdated( $itemRevision );
	}

	public function testEntityFromKnownSourceDeleted_entityDeletedPassedToRelevantServiceContainer() {
		$itemId = new ItemId( 'Q1' );

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'entityDeleted' )
			->with( $itemId );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'entityDeleted' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->entityDeleted( $itemId );
	}

	public function testRedirectOfEntityFromKnownSourceDeleted_redirectUpdatedPassedToRelevantServiceContainer() {
		$itemRedirect = new EntityRedirect( new ItemId( 'Q1' ), new ItemId( 'Q300' ) );
		$revisionId = 333;

		$itemServices = $this->createMock( SingleEntitySourceServices::class );
		$itemServices->expects( $this->atLeastOnce() )
			->method( 'redirectUpdated' )
			->with( $itemRedirect, $revisionId );
		$propertyServices = $this->createMock( SingleEntitySourceServices::class );
		$propertyServices->expects( $this->never() )
			->method( 'redirectUpdated' );

		$services = $this->newMultipleEntitySourceServices( [ 'items' => $itemServices, 'props' => $propertyServices ] );

		$services->redirectUpdated( $itemRedirect, $revisionId );
	}

	/**
	 * @param SingleEntitySourceServices[] $perSourceServices
	 * @return MultipleEntitySourceServices
	 */
	private function newMultipleEntitySourceServices( array $perSourceServices ) {
		return new MultipleEntitySourceServices(
			new EntitySourceDefinitions( [
				new EntitySource( 'items', 'itemdb', [ 'item' => [ 'namespaceId' => 100, 'slot' => 'main' ] ], '', '' ),
				new EntitySource( 'props', 'propb', [ 'property' => [ 'namespaceId' => 200, 'slot' => 'main' ] ], '', 'props' ),
			] ),
			new GenericServices( new EntityTypeDefinitions( [] ), [], [] ),
			$perSourceServices
		);
	}

}
