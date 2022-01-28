<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use WANObjectCache;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\CleanTermsIfUnusedJob;
use Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\MockJobQueueFactory;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStoreWriter
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabasePropertyTermStoreWriterTest extends MediaWikiIntegrationTestCase {

	use DatabaseTermStoreWriterTestGetTermsTrait;
	use LocalRepoDbTestHelper;

	/** @var NumericPropertyId */
	private $p1;

	/** @var Fingerprint */
	private $fingerprint1;

	/** @var Fingerprint */
	private $fingerprint2;

	/** @var Fingerprint */
	private $fingerprintEmpty;

	private $mockJobQueueFactory;

	private $jobQueueMock;

	protected function setUp(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		parent::setUp();
		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_property_terms';

		$this->p1 = new NumericPropertyId( 'P1' );
		$this->fingerprint1 = new Fingerprint(
			new TermList( [ new Term( 'en', 'some label' ) ] ),
			new TermList( [ new Term( 'en', 'description' ) ] )
		);
		$this->fingerprint2 = new Fingerprint(
			new TermList( [ new Term( 'en', 'another label' ) ] ),
			new TermList( [ new Term( 'en', 'description' ) ] )
		);
		$this->fingerprintEmpty = new Fingerprint();
		$this->mockJobQueueFactory = new MockJobQueueFactory( $this );
		$this->jobQueueMock = $this->mockJobQueueFactory->getJobQueueGroupMockExpectingTermInLangsIds();
	}

	private function getPropertyTermStoreWriter(
		$jobQueueMockOverride = null
	) {
		if ( $jobQueueMockOverride === null ) {
			$jobQueue = $this->jobQueueMock;
		} else {
			$jobQueue = $this->getServiceContainer()->getJobQueueGroup();
		}

		$repoDb = $this->getRepoDomainDb();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$repoDb,
			WANObjectCache::newEmpty()
		);
		return new DatabasePropertyTermStoreWriter( $repoDb, $jobQueue,
			new DatabaseTermInLangIdsAcquirer( $repoDb, $typeIdsStore ),
			new DatabaseTermInLangIdsResolver( $typeIdsStore, $typeIdsStore, $repoDb ),
			new StringNormalizer()
		);
	}

	public function testStoreAndGetTerms() {
		$store = $this->getPropertyTermStoreWriter();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$fingerprint = $this->getTermsForProperty( $this->p1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreEmptyAndGetTerms() {
		$store = $this->getPropertyTermStoreWriter();

		$store->storeTerms(
			$this->p1,
			$this->fingerprintEmpty
		);

		$fingerprint = $this->getTermsForProperty( $this->p1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testDeleteTermsWithoutStore() {
		$store = $this->getPropertyTermStoreWriter();

		$store->deleteTerms( $this->p1 );
		$this->assertTrue( true, 'did not throw an error' );
	}

	public function testStoreSameFingerprintTwiceAndGetTerms() {
		$store = $this->getPropertyTermStoreWriter();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);
		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$fingerprint = $this->getTermsForProperty( $this->p1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreTwoFingerprintsAndGetTerms() {
		$store = $this->getPropertyTermStoreWriter();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);
		$store->storeTerms(
			$this->p1,
			$this->fingerprint2
		);

		$fingerprint = $this->getTermsForProperty( $this->p1 );

		$this->assertEquals( $this->fingerprint2, $fingerprint );
	}

	public function testStoreAndDeleteAndGetTerms() {
		$store = $this->getPropertyTermStoreWriter();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$store->deleteTerms( $this->p1 );

		$fingerprint = $this->getTermsForProperty( $this->p1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testRemovingSharedAndUnsharedTermDoesntRemoveUsedTerms() {
		$store = $this->getPropertyTermStoreWriter();
		$sharedTerm = new Term( 'en', 'Cat' );
		$propertyFingerprint = new Fingerprint(
			new TermList( [ $sharedTerm ] ),
			new TermList( [ new Term( 'en', 'Dog' ) ] )
		);
		$property2Fingerprint = new Fingerprint(
			new TermList( [ $sharedTerm ] ),
			new TermList( [ new Term( 'en', 'Goat' ) ] )
		);
		$property1 = new NumericPropertyId( 'P1' );
		$property2 = new NumericPropertyId( 'P2' );
		$store->storeTerms( $property1, $propertyFingerprint );
		$store->storeTerms( $property2, $property2Fingerprint );

		$store->storeTerms( $property1, $this->fingerprintEmpty );

		$this->assertTrue( $this->getTermsForProperty( $property2 )->equals( $property2Fingerprint ) );
	}

	public function testStoreTermsTriggersCleanUpRemovedTermsJobOnlyForThisItem() {
		$termInLangId1 = 456738929;
		$termInLangId2 = 456738925;
		$termInLangId3 = 456738923;
		$this->insertPropertyTermRow( $this->p1->getNumericId(), $termInLangId1 );
		$this->insertPropertyTermRow( $this->p1->getNumericId(), $termInLangId2 );
		$itemidTwo = 2;
		$this->insertPropertyTermRow( $itemidTwo, $termInLangId3 );

		$this->jobQueueMock = $this->getJobQueueGroupMockExpectingTermInLangsIds( [ $termInLangId1, $termInLangId2 ] );
		$store = $this->getPropertyTermStoreWriter();

		$store->storeTerms(
			$this->p1,
			$this->fingerprintEmpty
		);
	}

	private function insertPropertyTermRow( int $itemid, int $termInLangId ): void {
		$this->db->insert( 'wbt_property_terms', [ 'wbpt_property_id' => $itemid, 'wbpt_term_in_lang_id' => $termInLangId ] );
	}

	public function testStoreTermsTriggersCleanUpRemovedTermsJobOnlyForRemovedTerms() {
		$termInLangId1 = 456738929;
		$termInLangId2 = 456738925;

		$this->jobQueueMock = $this->getJobQueueGroupMockExpectingTermInLangsIds( [ $termInLangId1, $termInLangId2 ] );
		$store = $this->getPropertyTermStoreWriter();

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$this->insertPropertyTermRow( $this->p1->getNumericId(), $termInLangId1 );
		$this->insertPropertyTermRow( $this->p1->getNumericId(), $termInLangId2 );

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);
	}

	public function testDeleteTermsTriggersUpRemovedTermsJob() {
		// check multiple
		$termInLangId1 = 456738929;
		$termInLangId2 = 456738925;
		$termInLangId3 = 456738923;
		$this->insertPropertyTermRow( $this->p1->getNumericId(), $termInLangId1 );
		$this->insertPropertyTermRow( $this->p1->getNumericId(), $termInLangId2 );
		$propertyidTwo = 2;
		$this->insertPropertyTermRow( $propertyidTwo, $termInLangId3 );
		$this->jobQueueMock = $this->getJobQueueGroupMockExpectingTermInLangsIds( [ $termInLangId1, $termInLangId2 ] );
		$store = $this->getPropertyTermStoreWriter();

		$store->deleteTerms( $this->p1 );
	}

	public function testDeleteTermsDoesNotTriggerIfNoDeletesHappen() {
		$this->jobQueueMock = $this->getJobQueueMockExpectingNoCalls();
		$store = $this->getPropertyTermStoreWriter();

		$store->deleteTerms( $this->p1 );
	}

	public function testStoreTermsDoesNotTriggerIfNonDestructive() {
		$this->jobQueueMock = $this->getJobQueueMockExpectingNoCalls();
		$store = $this->getPropertyTermStoreWriter();

		$store->storeTerms(
			$this->p1,
			$this->fingerprintEmpty
		);
	}

	public function testStoresAndGetsUTF8Text() {
		$store = $this->getPropertyTermStoreWriter();

		$this->fingerprint1->setDescription(
			'utf8',
			'ఒక వ్యక్తి లేదా సంస్థ సాధించిన రికార్డు. ఈ రికార్డును సాధించిన కోల్పోయిన తేదీలను చూపేందుకు క్'
		);

		$store->storeTerms(
			$this->p1,
			$this->fingerprint1
		);

		$fingerprint = $this->getTermsForProperty( $this->p1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testCleanupJobWorks() {
		$jobQueue = $this->getServiceContainer()->getJobQueueGroup();
		$store = $this->getPropertyTermStoreWriter( $jobQueue );
		$fingerprint1 = new Fingerprint( new Termlist( [ new Term( 'en', 'p--aaaaaaaaaaaaaa1' ) ] ) );
		$fingerprint2 = new Fingerprint( new Termlist( [ new Term( 'en', 'p--aaaaaaaaaaaaaa2' ) ] ) );

		// Make sure there are not already any cleanup jobs
		$jobQueue->get( CleanTermsIfUnusedJob::JOB_NAME )->delete();

		// Schedule a job by causing a term text to be removed and need cleaning up
		$store->storeTerms( $this->p1, $fingerprint1 );
		$store->storeTerms( $this->p1, $fingerprint2 );

		// A job should now be scheduled cleaning up "p--aaaaaaaaaaaaaa1", which we can run
		$jobQueue->get( CleanTermsIfUnusedJob::JOB_NAME )->pop()->run();

		// Make sure the cleanup happened
		$this->assertSame( 0, $this->db->selectRowCount( 'wbt_text', '*', [ 'wbx_text' => 'a--aaaaaaaaaaaaaa1' ] ) );
	}

	private function getJobQueueGroupMockExpectingTermInLangsIds( array $termInLangIds ) {
		return $this->mockJobQueueFactory->getJobQueueGroupMockExpectingTermInLangsIds( $termInLangIds );
	}

	private function getJobQueueMockExpectingNoCalls() {
		return $this->mockJobQueueFactory->getJobQueueMockExpectingNoCalls();
	}

}
