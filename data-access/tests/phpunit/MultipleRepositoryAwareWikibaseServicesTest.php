<?php

namespace Wikibase\DataAccess\Tests;

use PHPUnit4And6Compat;
use Serializers\Serializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\MultipleRepositoryAwareWikibaseServices;
use Wikibase\DataAccess\MultiRepositoryServices;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Entity\EntityPrefetcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\TermSearchInteractorFactory;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\DataAccess\MultipleRepositoryAwareWikibaseServices
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultipleRepositoryAwareWikibaseServicesTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function newMultipleRepositoryAwareWikibaseServices() {
		return new MultipleRepositoryAwareWikibaseServices(
			new BasicEntityIdParser(),
			new EntityIdComposer( [] ),
			$this->getRepositoryDefinitions(),
			new EntityTypeDefinitions( [] ),
			new DataAccessSettings( 1, true, false ),
			$this->getMultiRepoServiceWiring(),
			[]
		);
	}

	private function getRepositoryDefinitions() {
		return new RepositoryDefinitions(
			[ '' => [
				'database' => false,
				'base-uri' => 'http://foo',
				'entity-namespaces' => [],
				'prefix-mapping' => [],
			] ],
			new EntityTypeDefinitions( [] )
		);
	}

	private function getMultiRepoServiceWiring() {
		$testCase = $this;

		return [
			'EntityInfoBuilder' => function () use ( $testCase ) {
				return $testCase->getMock( EntityInfoBuilder::class );
			},
			'EntityPrefetcher' => function () use ( $testCase ) {
				return $testCase->getMock( EntityPrefetcher::class );
			},
			'EntityRevisionLookup' => function () use ( $testCase ) {
				return $testCase->getMock( EntityRevisionLookup::class );
			},
			'EntityStoreWatcher' => function () use ( $testCase ) {
				return $testCase->getMock( EntityStoreWatcher::class );
			},
			'PropertyInfoLookup' => function () use ( $testCase ) {
				return $testCase->getMock( PropertyInfoLookup::class );
			},
			'TermBuffer' => function () use ( $testCase ) {
				return $testCase->getMock( TermBuffer::class );
			},
			'TermSearchInteractorFactory' => function () use ( $testCase ) {
				return $testCase->getMock( TermSearchInteractorFactory::class );
			},
		];
	}

	public function testGetEntityInfoBuilder() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( EntityInfoBuilder::class, $wikibaseServices->getEntityInfoBuilder() );
	}

	public function testGetEntityNamespaceLookup() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( EntityNamespaceLookup::class, $wikibaseServices->getEntityNamespaceLookup() );
	}

	public function testGetEntityPrefetcher() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( EntityPrefetcher::class, $wikibaseServices->getEntityPrefetcher() );
	}

	public function testGetEntityRevisionLookup() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( EntityRevisionLookup::class, $wikibaseServices->getEntityRevisionLookup() );
	}

	public function testGetFullEntitySerializer() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( Serializer::class, $wikibaseServices->getFullEntitySerializer() );
	}

	public function testGetCompactEntitySerializer() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( Serializer::class, $wikibaseServices->getCompactEntitySerializer() );
	}

	public function testGetStorageEntitySerializer() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( Serializer::class, $wikibaseServices->getStorageEntitySerializer() );
	}

	public function testGetEntityStoreWatcher() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( EntityStoreWatcher::class, $wikibaseServices->getEntityStoreWatcher() );
	}

	public function testGetLanguageFallbackChainFactory() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( LanguageFallbackChainFactory::class, $wikibaseServices->getLanguageFallbackChainFactory() );
	}

	public function testGetPropertyInfoLookup() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( PropertyInfoLookup::class, $wikibaseServices->getPropertyInfoLookup() );
	}

	public function testGetBaseDataModelSerializerFactory() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( SerializerFactory::class, $wikibaseServices->getBaseDataModelSerializerFactory() );
	}

	public function testGetCompactBaseDataModelSerializerFactory() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( SerializerFactory::class, $wikibaseServices->getCompactBaseDataModelSerializerFactory() );
	}

	public function testGetStringNormalizer() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( StringNormalizer::class, $wikibaseServices->getStringNormalizer() );
	}

	public function testGetTermBuffer() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( TermBuffer::class, $wikibaseServices->getTermBuffer() );
	}

	public function testGetTermSearchInteractorFactory() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf(
			TermSearchInteractorFactory::class,
			$wikibaseServices->getTermSearchInteractorFactory()
		);
	}

	public function testGetServiceNames() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$serviceNames = $wikibaseServices->getServiceNames();

		$this->assertEquals(
			[
				'EntityInfoBuilder',
				'EntityNamespaceLookup',
				'EntityPrefetcher',
				'EntityRevisionLookup',
				'FullEntitySerializer',
				'CompactEntitySerializer',
				'StorageEntitySerializer',
				'EntityStoreWatcher',
				'LanguageFallbackChainFactory',
				'PropertyInfoLookup',
				'BaseDataModelSerializerFactory',
				'CompactBaseDataModelSerializerFactory',
				'StringNormalizer',
				'TermBuffer',
				'TermSearchInteractorFactory',
			],
			$serviceNames
		);
	}

	public function testGetMultiRepositoryServices() {
		$wikibaseServices = $this->newMultipleRepositoryAwareWikibaseServices();

		$this->assertInstanceOf( MultiRepositoryServices::class, $wikibaseServices->getMultiRepositoryServices() );
	}

}
