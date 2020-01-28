<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use InvalidArgumentException;
use MediaWikiTestCase;
use WANObjectCache;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\Tests\DataAccessSettingsFactory;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStore;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLBFactory;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLoadBalancer;
use Wikibase\StringNormalizer;
use Wikibase\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStore
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabasePropertyTermStoreTest extends MediaWikiTestCase {

	/** @var PropertyId */
	private $p1;

	/** @var Fingerprint */
	private $fingerprint1;

	/** @var Fingerprint */
	private $fingerprint2;

	/** @var Fingerprint */
	private $fingerprintEmpty;

	protected function setUp() : void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		parent::setUp();
		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_property_terms';

		$this->p1 = new PropertyId( 'P1' );
		$this->fingerprint1 = new Fingerprint(
			new TermList( [ new Term( 'en', 'some label' ) ] ),
			new TermList( [ new Term( 'en', 'description' ) ] )
		);
		$this->fingerprint2 = new Fingerprint(
			new TermList( [ new Term( 'en', 'another label' ) ] ),
			new TermList( [ new Term( 'en', 'description' ) ] )
		);
		$this->fingerprintEmpty = new Fingerprint();
	}

	private function getPropertyTermStore() {
		$loadBalancer = new FakeLoadBalancer( [
			'dbr' => $this->db,
		] );
		$lbFactory = new FakeLBFactory( [
			'lb' => $loadBalancer
		] );
		$typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			WANObjectCache::newEmpty()
		);
		return new DatabasePropertyTermStore(
			$loadBalancer,
			new DatabaseTermIdsAcquirer(
				$lbFactory,
				$typeIdsStore
			),
			new DatabaseTermIdsResolver(
				$typeIdsStore,
				$typeIdsStore,
				$loadBalancer
			),
			new DatabaseTermIdsCleaner(
				$loadBalancer
			),
			new StringNormalizer(),
			new UnusableEntitySource(),
			DataAccessSettingsFactory::repositoryPrefixBasedFederation()
		);
	}

	private function getPropertyTermStore_entitySourceBasedFederation() {
		$loadBalancer = new FakeLoadBalancer( [
			'dbr' => $this->db,
		] );
		$lbFactory = new FakeLBFactory( [
			'lb' => $loadBalancer
		] );
		$typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			WANObjectCache::newEmpty()
		);
		return new DatabasePropertyTermStore(
			$loadBalancer,
			new DatabaseTermIdsAcquirer(
				$lbFactory,
				$typeIdsStore
			),
			new DatabaseTermIdsResolver(
				$typeIdsStore,
				$typeIdsStore,
				$loadBalancer
			),
			new DatabaseTermIdsCleaner(
				$loadBalancer
			),
			new StringNormalizer(),
			$this->getPropertySource(),
			DataAccessSettingsFactory::entitySourceBasedFederation()
		);
	}

	private function getPropertySource() {
		return new EntitySource( 'test', false, [ 'property' => [ 'namespaceId' => 123, 'slot' => 'main' ] ], '', '', '', '' );
	}

	public function testStoreAndGetTerms() {
		$store = $this->getPropertyTermStore();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreAndGetTerms_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testGetTermsWithoutStore() {
		$store = $this->getPropertyTermStore();

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testGetTermsWithoutStore_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testStoreEmptyAndGetTerms() {
		$store = $this->getPropertyTermStore();

		$store->storeTerms(
			$this->p1,
			$this->fingerprintEmpty
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testStoreEmptyAndGetTerms_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$store->storeTerms(
			$this->p1,
			$this->fingerprintEmpty
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testDeleteTermsWithoutStore() {
		$store = $this->getPropertyTermStore();

		$store->deleteTerms( $this->p1 );
		$this->assertTrue( true, 'did not throw an error' );
	}

	public function testDeleteTermsWithoutStore_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$store->deleteTerms( $this->p1 );
		$this->assertTrue( true, 'did not throw an error' );
	}

	public function testStoreSameFingerprintTwiceAndGetTerms() {
		$store = $this->getPropertyTermStore();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);
		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreSameFingerprintTwiceAndGetTerms_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);
		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreTwoFingerprintsAndGetTerms() {
		$store = $this->getPropertyTermStore();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);
		$store->storeTerms(
			$this->p1,
			$this->fingerprint2
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertEquals( $this->fingerprint2, $fingerprint );
	}

	public function testStoreTwoFingerprintsAndGetTerms_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);
		$store->storeTerms(
			$this->p1,
			$this->fingerprint2
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertEquals( $this->fingerprint2, $fingerprint );
	}

	public function testStoreAndDeleteAndGetTerms() {
		$store = $this->getPropertyTermStore();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$store->deleteTerms( $this->p1 );

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testStoreAndDeleteAndGetTerms_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$store->deleteTerms( $this->p1 );

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testStoreTermsCleansUpRemovedTerms() {
		$store = $this->getPropertyTermStore();

		$store->storeTerms(
			$this->p1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'The real name of UserName is John Doe' ) ] )
			)
		);
		$store->storeTerms(
			$this->p1,
			$this->fingerprintEmpty
		);

		$this->assertSelect(
			'wbt_text',
			'wbx_text',
			[ 'wbx_text' => 'The real name of UserName is John Doe' ],
			[ /* empty */ ]
		);
	}

	public function testStoreTermsCleansUpRemovedTerms_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$store->storeTerms(
			$this->p1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'The real name of UserName is John Doe' ) ] )
			)
		);
		$store->storeTerms(
			$this->p1,
			$this->fingerprintEmpty
		);

		$this->assertSelect(
			'wbt_text',
			'wbx_text',
			[ 'wbx_text' => 'The real name of UserName is John Doe' ],
			[ /* empty */ ]
		);
	}

	public function testDeleteTermsCleansUpRemovedTerms() {
		$store = $this->getPropertyTermStore();

		$store->storeTerms(
			$this->p1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'The real name of UserName is John Doe' ) ] )
			)
		);
		$store->deleteTerms( $this->p1 );

		$this->assertSelect(
			'wbt_text',
			'wbx_text',
			[ 'wbx_text' => 'The real name of UserName is John Doe' ],
			[ /* empty */ ]
		);
	}

	public function testDeleteTermsCleansUpRemovedTerms_entitySourceBasedFederation() {
		$store = $this->getPropertyTermStore_entitySourceBasedFederation();

		$store->storeTerms(
			$this->p1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'The real name of UserName is John Doe' ) ] )
			)
		);
		$store->deleteTerms( $this->p1 );

		$this->assertSelect(
			'wbt_text',
			'wbx_text',
			[ 'wbx_text' => 'The real name of UserName is John Doe' ],
			[ /* empty */ ]
		);
	}

	public function testStoreTerms_throwsForForeignPropertyId() {
		$store = $this->getPropertyTermStore();

		$this->expectException( InvalidArgumentException::class );
		$store->storeTerms( new PropertyId( 'wd:P1' ), $this->fingerprintEmpty );
	}

	public function testDeleteTerms_throwsForForeignPropertyId() {
		$store = $this->getPropertyTermStore();

		$this->expectException( InvalidArgumentException::class );
		$store->deleteTerms( new PropertyId( 'wd:P1' ) );
	}

	public function testGetTerms_throwsForForeignPropertyId() {
		$store = $this->getPropertyTermStore();

		$this->expectException( InvalidArgumentException::class );
		$store->getTerms( new PropertyId( 'wd:P1' ) );
	}

	public function testStoreTerms_throwsForNonPropertyEntitySource_entitySourceBasedFederation() {
		$store = $this->getTermStoreNotHandlingProperties();

		$this->expectException( InvalidArgumentException::class );

		$store->storeTerms( new PropertyId( 'P1' ), $this->fingerprintEmpty );
	}

	public function testDeleteTerms_throwsForNonPropertyEntitySource_entitySourceBasedFederation() {
		$store = $this->getTermStoreNotHandlingProperties();

		$this->expectException( InvalidArgumentException::class );
		$store->deleteTerms( new PropertyId( 'P1' ) );
	}

	public function testGetTerms_throwsForNonPropertyEntitySource_entitySourceBasedFederation() {
		$store = $this->getTermStoreNotHandlingProperties();

		$this->expectException( InvalidArgumentException::class );

		$store->getTerms( new PropertyId( 'P1' ) );
	}

	private function getTermStoreNotHandlingProperties() {
		$loadBalancer = new FakeLoadBalancer( [
			'dbr' => $this->db,
		] );
		$typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			WANObjectCache::newEmpty()
		);

		return new DatabasePropertyTermStore(
			$loadBalancer,
			new DatabaseTermIdsAcquirer(
				new FakeLBFactory( [
					'lb' => $loadBalancer
				] ),
				$typeIdsStore
			),
			new DatabaseTermIdsResolver(
				$typeIdsStore,
				$typeIdsStore,
				$loadBalancer
			),
			new DatabaseTermIdsCleaner(
				$loadBalancer
			),
			new StringNormalizer(),
			new EntitySource( 'test', false, [ 'item' => [ 'namespaceId' => 123, 'slot' => 'main' ] ], '', '', '', '' ),
			DataAccessSettingsFactory::entitySourceBasedFederation()
		);
	}

	public function testStoresAndGetsUTF8Text() {
		$store = $this->getPropertyTermStore();

		$this->fingerprint1->setDescription(
			'utf8',
			'ఒక వ్యక్తి లేదా సంస్థ సాధించిన రికార్డు. ఈ రికార్డును సాధించిన కోల్పోయిన తేదీలను చూపేందుకు క్'
		);

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$fingerprint = $store->getTerms( $this->p1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

}
