<?php

namespace Wikibase\Repo\Tests\Store\Sql;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\SqlStore;
use Wikibase\Store\EntityIdLookup;

/**
 * @covers Wikibase\SqlStore
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseRepo
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class SqlStoreTest extends MediaWikiTestCase {

	public function newInstance() {
		$contentCodec = $this->getMockBuilder( EntityContentDataCodec::class )
			->disableOriginalConstructor()
			->getMock();

		return new SqlStore(
			$contentCodec,
			$this->getMock( EntityIdParser::class ),
			$this->getMock( EntityIdLookup::class ),
			$this->getMock( EntityTitleLookup::class )
		);
	}

	public function testGetTermIndex() {
		$service = $this->newInstance()->getTermIndex();
		$this->assertInstanceOf( 'Wikibase\TermIndex', $service );
	}

	public function testGetLabelConflictFinder() {
		$service = $this->newInstance()->getLabelConflictFinder();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\LabelConflictFinder', $service );
	}

	public function testNewIdGenerator() {
		$service = $this->newInstance()->newIdGenerator();
		$this->assertInstanceOf( 'Wikibase\IdGenerator', $service );
	}

	public function testNewSiteLinkStore() {
		$service = $this->newInstance()->newSiteLinkStore();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\SiteLinkStore', $service );
	}

	public function testNewEntityPerPage() {
		$service = $this->newInstance()->newEntityPerPage();
		$this->assertInstanceOf( 'Wikibase\Repo\Store\EntityPerPage', $service );
	}

	public function testGetEntityRedirectLookup() {
		$service = $this->newInstance()->getEntityRedirectLookup();
		$this->assertInstanceOf(
			'Wikibase\DataModel\Services\Lookup\EntityRedirectLookup',
			$service
		);
	}

	public function testGetEntityLookup() {
		$service = $this->newInstance()->getEntityLookup();
		$this->assertInstanceOf( 'Wikibase\DataModel\Services\Lookup\EntityLookup', $service );
	}

	public function testGetEntityStoreWatcher() {
		$service = $this->newInstance()->getEntityStoreWatcher();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityStoreWatcher', $service );
	}

	public function testGetEntityStore() {
		$service = $this->newInstance()->getEntityStore();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityStore', $service );
	}

	public function testGetEntityRevisionLookup() {
		$service = $this->newInstance()->getEntityRevisionLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityRevisionLookup', $service );
	}

	public function testGetEntityInfoBuilderFactory() {
		$service = $this->newInstance()->getEntityInfoBuilderFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityInfoBuilderFactory', $service );
	}

	public function testGetPropertyInfoStore() {
		$service = $this->newInstance()->getPropertyInfoStore();
		$this->assertInstanceOf( 'Wikibase\PropertyInfoStore', $service );
	}

	public function testGetSiteLinkConflictLookup() {
		$service = $this->newInstance()->getSiteLinkConflictLookup();
		$this->assertInstanceOf( 'Wikibase\Repo\Store\SiteLinkConflictLookup', $service );
	}

	public function testGetEntityPrefetcher() {
		$service = $this->newInstance()->getEntityPrefetcher();
		$this->assertInstanceOf(
			'Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor',
			$service
		);
	}

	public function testGetChangeLookup() {
		$service = $this->newInstance()->getChangeLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\ChangeLookup', $service );
	}

	public function testGetChangeStore() {
		$service = $this->newInstance()->getChangeStore();
		$this->assertInstanceOf( 'Wikibase\Repo\Store\Sql\SqlChangeStore', $service );
	}

}
