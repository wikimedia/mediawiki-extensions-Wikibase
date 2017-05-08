<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectLookup;
use Wikibase\IdGenerator;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\EntityChangeLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Lib\Store\LabelConflictFinder;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\SiteLinkStore;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Store\Sql\SqlChangeStore;
use Wikibase\SqlStore;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * @covers Wikibase\SqlStore
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class SqlStoreTest extends MediaWikiTestCase {

	public function newInstance() {
		$changeFactory = $this->getMockBuilder( EntityChangeFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$contentCodec = $this->getMockBuilder( EntityContentDataCodec::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdComposer = $this->getMockBuilder( EntityIdComposer::class )
			->disableOriginalConstructor()
			->getMock();

		return new SqlStore(
			$changeFactory,
			$contentCodec,
			$this->getMock( EntityIdParser::class ),
			$entityIdComposer,
			$this->getMock( EntityIdLookup::class ),
			$this->getMock( EntityTitleStoreLookup::class ),
			new EntityNamespaceLookup( [] )
		);
	}

	public function testGetTermIndex() {
		$service = $this->newInstance()->getTermIndex();
		$this->assertInstanceOf( TermIndex::class, $service );
	}

	public function testGetLabelConflictFinder() {
		$service = $this->newInstance()->getLabelConflictFinder();
		$this->assertInstanceOf( LabelConflictFinder::class, $service );
	}

	public function testNewIdGenerator() {
		$service = $this->newInstance()->newIdGenerator();
		$this->assertInstanceOf( IdGenerator::class, $service );
	}

	public function testNewSiteLinkStore() {
		$service = $this->newInstance()->newSiteLinkStore();
		$this->assertInstanceOf( SiteLinkStore::class, $service );
	}

	public function testGetEntityRedirectLookup() {
		$service = $this->newInstance()->getEntityRedirectLookup();
		$this->assertInstanceOf( EntityRedirectLookup::class, $service );
	}

	public function testGetEntityLookup() {
		$service = $this->newInstance()->getEntityLookup();
		$this->assertInstanceOf( EntityLookup::class, $service );
	}

	public function testGetEntityStoreWatcher() {
		$service = $this->newInstance()->getEntityStoreWatcher();
		$this->assertInstanceOf( EntityStoreWatcher::class, $service );
	}

	public function testGetEntityStore() {
		$service = $this->newInstance()->getEntityStore();
		$this->assertInstanceOf( EntityStore::class, $service );
	}

	public function testGetEntityRevisionLookup() {
		$service = $this->newInstance()->getEntityRevisionLookup();
		$this->assertInstanceOf( EntityRevisionLookup::class, $service );
	}

	public function testGetEntityInfoBuilderFactory() {
		$service = $this->newInstance()->getEntityInfoBuilderFactory();
		$this->assertInstanceOf( EntityInfoBuilderFactory::class, $service );
	}

	public function testGetPropertyInfoLookup() {
		$service = $this->newInstance()->getPropertyInfoLookup();
		$this->assertInstanceOf( PropertyInfoLookup::class, $service );
	}

	public function testGetPropertyInfoStore() {
		$service = $this->newInstance()->getPropertyInfoStore();
		$this->assertInstanceOf( PropertyInfoStore::class, $service );
	}

	public function testGetSiteLinkConflictLookup() {
		$service = $this->newInstance()->getSiteLinkConflictLookup();
		$this->assertInstanceOf( SiteLinkConflictLookup::class, $service );
	}

	public function testGetEntityPrefetcher() {
		$service = $this->newInstance()->getEntityPrefetcher();
		$this->assertInstanceOf( PrefetchingWikiPageEntityMetaDataAccessor::class, $service );
	}

	public function testGetEntityChangeLookup() {
		$service = $this->newInstance()->getEntityChangeLookup();
		$this->assertInstanceOf( EntityChangeLookup::class, $service );
	}

	public function testGetChangeStore() {
		$service = $this->newInstance()->getChangeStore();
		$this->assertInstanceOf( SqlChangeStore::class, $service );
	}

}
