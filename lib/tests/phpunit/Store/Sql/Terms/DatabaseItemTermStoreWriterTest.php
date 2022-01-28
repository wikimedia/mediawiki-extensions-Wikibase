<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiIntegrationTestCase;
use WANObjectCache;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\CleanTermsIfUnusedJob;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\MockJobQueueFactory;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabaseItemTermStoreWriterTest extends MediaWikiIntegrationTestCase {

	use DatabaseTermStoreWriterTestGetTermsTrait;
	use LocalRepoDbTestHelper;

	/** @var ItemId */
	private $i1;

	/** @var Fingerprint */
	private $fingerprint1;

	/** @var Fingerprint */
	private $fingerprint2;

	/** @var Fingerprint */
	private $fingerprintEmpty;

	private $jobQueueMock;

	/** * @var MockJobQueueFactory */
	private $mockJobQueueFactory;

	protected function setUp(): void {
		parent::setUp();
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_item_terms';
		$this->tablesUsed[] = 'wbt_property_terms';

		$this->i1 = new ItemId( 'Q1' );

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

	private function getItemTermStoreWriter(
		$jobQueueMockOverride = null
	): DatabaseItemTermStoreWriter {
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

		return new DatabaseItemTermStoreWriter( $repoDb, $jobQueue,
			new DatabaseTermInLangIdsAcquirer( $repoDb, $typeIdsStore ),
			new DatabaseTermInLangIdsResolver( $typeIdsStore, $typeIdsStore, $repoDb ),
			new StringNormalizer()
		);
	}

	public function testStoreAndGetTerms() {
		$store = $this->getItemTermStoreWriter();

		$store->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->getTermsForItem( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreEmptyAndGetTerms() {
		$store = $this->getItemTermStoreWriter();

		$store->storeTerms(
			$this->i1,
			$this->fingerprintEmpty
		);

		$fingerprint = $this->getTermsForItem( $this->i1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testDeleteTermsWithoutStore() {
		$store = $this->getItemTermStoreWriter();

		$store->deleteTerms( $this->i1 );
		$this->assertTrue( true, 'did not throw an error' );
	}

	public function testStoreSameFingerprintTwiceAndGetTerms() {
		$store = $this->getItemTermStoreWriter();

		$store->storeTerms(
			$this->i1,
			$this->fingerprint1
		);
		$store->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->getTermsForItem( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreTwoFingerprintsAndGetTerms() {
		$store = $this->getItemTermStoreWriter();

		$store->storeTerms(
			$this->i1,
			$this->fingerprint1
		);
		$store->storeTerms(
			$this->i1,
			$this->fingerprint2
		);

		$fingerprint = $this->getTermsForItem( $this->i1 );

		$this->assertEquals( $this->fingerprint2, $fingerprint );
	}

	public function testRemovingSharedAndUnsharedTermDoesntRemoveUsedTerms() {
		$store = $this->getItemTermStoreWriter();
		$sharedTerm = new Term( 'en', 'Cat' );
		$item1Fingerprint = new Fingerprint(
			new TermList( [ $sharedTerm ] ),
			new TermList( [ new Term( 'en', 'Dog' ) ] )
		);
		$item2Fingerprint = new Fingerprint(
			new TermList( [ $sharedTerm ] ),
			new TermList( [ new Term( 'en', 'Goat' ) ] )
		);
		$item1 = new ItemId( 'Q1' );
		$item2 = new ItemId( 'Q2' );
		$store->storeTerms( $item1, $item1Fingerprint );
		$store->storeTerms( $item2, $item2Fingerprint );

		$store->storeTerms( $item1, $this->fingerprintEmpty );

		$this->assertTrue( $this->getTermsForItem( $item2 )->equals( $item2Fingerprint ) );
	}

	public function testStoreTermsTriggersCleanUpRemovedTermsJobOnlyForThisItem() {
		$termInLangId1 = 456738929;
		$termInLangId2 = 456738925;
		$termInLangId3 = 456738923;
		$this->insertItemTermRow( $this->i1->getNumericId(), $termInLangId1 );
		$this->insertItemTermRow( $this->i1->getNumericId(), $termInLangId2 );
		$itemidTwo = 2;
		$this->insertItemTermRow( $itemidTwo, $termInLangId3 );

		$this->jobQueueMock = $this->mockJobQueueFactory->getJobQueueGroupMockExpectingTermInLangsIds( [ $termInLangId1, $termInLangId2 ] );
		$store = $this->getItemTermStoreWriter();

		$store->storeTerms(
			$this->i1,
			$this->fingerprintEmpty
		);
	}

	public function testStoreTermsTriggersCleanUpRemovedTermsJobOnlyForRemovedTerms() {
		$termInLangId1 = 456738929;
		$termInLangId2 = 456738925;

		$this->jobQueueMock = $this->mockJobQueueFactory->getJobQueueGroupMockExpectingTermInLangsIds( [ $termInLangId1, $termInLangId2 ] );
		$store = $this->getItemTermStoreWriter();

		$store->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$this->insertItemTermRow( $this->i1->getNumericId(), $termInLangId1 );
		$this->insertItemTermRow( $this->i1->getNumericId(), $termInLangId2 );

		$store->storeTerms(
			$this->i1,
			$this->fingerprint1
		);
	}

	public function testDeleteTermsTriggersUpRemovedTermsJob() {
		// check multiple
		$termInLangId1 = 456738929;
		$termInLangId2 = 456738925;
		$termInLangId3 = 456738923;
		$this->insertItemTermRow( $this->i1->getNumericId(), $termInLangId1 );
		$this->insertItemTermRow( $this->i1->getNumericId(), $termInLangId2 );
		$itemidTwo = 2;
		$this->insertItemTermRow( $itemidTwo, $termInLangId3 );
		$this->jobQueueMock = $this->mockJobQueueFactory->getJobQueueGroupMockExpectingTermInLangsIds( [ $termInLangId1, $termInLangId2 ] );
		$store = $this->getItemTermStoreWriter();

		$store->deleteTerms( $this->i1 );
	}

	public function testDeleteTermsDoesNotTriggerIfNoDeletesHappen() {
		$this->jobQueueMock = $this->mockJobQueueFactory->getJobQueueMockExpectingNoCalls();
		$store = $this->getItemTermStoreWriter();

		$store->deleteTerms( $this->i1 );
	}

	public function testStoreTermsDoesNotTriggerIfNonDestructive() {
		$this->jobQueueMock = $this->mockJobQueueFactory->getJobQueueMockExpectingNoCalls();
		$store = $this->getItemTermStoreWriter();

		$store->storeTerms(
			$this->i1,
			$this->fingerprintEmpty
		);
	}

	public function testStoresAndGetsUTF8Text() {
		$store = $this->getItemTermStoreWriter();

		$this->fingerprint1->setDescription(
			'utf8',
			'ఒక వ్యక్తి లేదా సంస్థ సాధించిన రికార్డు. ఈ రికార్డును సాధించిన కోల్పోయిన తేదీలను చూపేందుకు క్'
		);

		$store->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->getTermsForItem( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testCleanupJobWorks() {
		$jobQueue = $this->getServiceContainer()->getJobQueueGroup();
		$store = $this->getItemTermStoreWriter( $jobQueue );
		$fingerprint1 = new Fingerprint( new Termlist( [ new Term( 'en', 'a--aaaaaaaaaaaaaa1' ) ] ) );
		$fingerprint2 = new Fingerprint( new Termlist( [ new Term( 'en', 'a--aaaaaaaaaaaaaa2' ) ] ) );

		// Make sure there are not already any cleanup jobs
		$jobQueue->get( CleanTermsIfUnusedJob::JOB_NAME )->delete();

		// Schedule a job by causing a term text to be removed and need cleaning up
		$store->storeTerms( $this->i1, $fingerprint1 );
		$store->storeTerms( $this->i1, $fingerprint2 );

		// A job should now be scheduled cleaning up "a--aaaaaaaaaaaaaa1", which we can run
		$jobQueue->get( CleanTermsIfUnusedJob::JOB_NAME )->pop()->run();

		// Make sure the cleanup happened
		$this->assertSame( 0, $this->db->selectRowCount( 'wbt_text', '*', [ 'wbx_text' => 'a--aaaaaaaaaaaaaa1' ] ) );
	}

	public function testT237984UnexpectedMissingTextRow() {
		$itemStoreWriter = $this->getItemTermStoreWriter();

		$repoDb = $this->getRepoDomainDb();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$repoDb,
			WANObjectCache::newEmpty()
		);
		$propertyTermStoreWriter = new DatabasePropertyTermStoreWriter( $repoDb,
			$this->getServiceContainer()->getJobQueueGroup(),
			new DatabaseTermInLangIdsAcquirer( $repoDb, $typeIdsStore ),
			new DatabaseTermInLangIdsResolver( $typeIdsStore, $typeIdsStore, $repoDb ), new StringNormalizer()
		);

		$propertyTermStoreWriter->storeTerms( new NumericPropertyId( 'P12' ), new Fingerprint(
			new TermList( [ new Term( 'nl', 'van' ) ] )
		) );
		$itemStoreWriter->storeTerms( new ItemId( 'Q99' ), new Fingerprint(
			new TermList(),
			new TermList( [ new Term( 'af', 'van' ) ] )
		) );

		// Store with empty fingerprint (will delete things)
		$itemStoreWriter->storeTerms( new ItemId( 'Q99' ), new Fingerprint() );

		$r = $this->getTermsForProperty( new NumericPropertyId( 'P12' ) );
		$this->assertTrue( $r->hasLabel( 'nl' ) );
		$this->assertEquals( 'van', $r->getLabel( 'nl' )->getText() );
	}

	private function insertItemTermRow( int $itemid, int $termInLangId ): void {
		$this->db->insert( 'wbt_item_terms', [ 'wbit_item_id' => $itemid, 'wbit_term_in_lang_id' => $termInLangId ] );
	}

}
