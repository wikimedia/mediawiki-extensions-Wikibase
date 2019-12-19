<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use InvalidArgumentException;
use MediaWikiTestCase;
use WANObjectCache;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStore;
use Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStore;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLBFactory;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\FakeLoadBalancer;
use Wikibase\StringNormalizer;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStore
 *
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DatabaseItemTermStoreTest extends MediaWikiTestCase {

	/** @var DatabaseItemTermStore */
	private $itemTermStore;

	/** @var ItemId */
	private $i1;

	/** @var Fingerprint */
	private $fingerprint1;

	/** @var Fingerprint */
	private $fingerprint2;

	/** @var Fingerprint */
	private $fingerprintEmpty;

	private $loadBalancer;
	private $lbFactory;
	private $typeIdsStore;

	protected function setUp() : void {
		parent::setUp();
		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_item_terms';

		$this->loadBalancer = new FakeLoadBalancer( [
			'dbr' => $this->db,
		] );
		$this->lbFactory = new FakeLBFactory( [
			'lb' => $this->loadBalancer
		] );
		$this->typeIdsStore = new DatabaseTypeIdsStore(
			$this->loadBalancer,
			WANObjectCache::newEmpty()
		);
		$this->itemTermStore = new DatabaseItemTermStore(
			$this->loadBalancer,
			new DatabaseTermIdsAcquirer(
				$this->lbFactory,
				$this->typeIdsStore
			),
			new DatabaseTermIdsResolver(
				$this->typeIdsStore,
				$this->typeIdsStore,
				$this->loadBalancer
			),
			new DatabaseTermIdsCleaner(
				$this->loadBalancer
			),
			new StringNormalizer()
		);
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
	}

	protected function getSchemaOverrides( IMaintainableDatabase $db ) {
		return [
			'scripts' => [
				__DIR__ . '/../../../../../../repo/sql/AddNormalizedTermsTablesDDL.sql',
			],
			'create' => [
				'wbt_item_terms',
				'wbt_property_terms',
				'wbt_term_in_lang',
				'wbt_text_in_lang',
				'wbt_text',
				'wbt_type',
			],
		];
	}

	public function testStoreAndGetTerms() {
		$this->itemTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->itemTermStore->getTerms( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testGetTermsWithoutStore() {
		$fingerprint = $this->itemTermStore->getTerms( $this->i1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testStoreEmptyAndGetTerms() {
		$this->itemTermStore->storeTerms(
			$this->i1,
			$this->fingerprintEmpty
		);

		$fingerprint = $this->itemTermStore->getTerms( $this->i1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testDeleteTermsWithoutStore() {
		$this->itemTermStore->deleteTerms( $this->i1 );
		$this->assertTrue( true, 'did not throw an error' );
	}

	public function testStoreSameFingerprintTwiceAndGetTerms() {
		$this->itemTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);
		$this->itemTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->itemTermStore->getTerms( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreTwoFingerprintsAndGetTerms() {
		$this->itemTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);
		$this->itemTermStore->storeTerms(
			$this->i1,
			$this->fingerprint2
		);

		$fingerprint = $this->itemTermStore->getTerms( $this->i1 );

		$this->assertEquals( $this->fingerprint2, $fingerprint );
	}

	public function testStoreAndDeleteAndGetTerms() {
		$this->itemTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$this->itemTermStore->deleteTerms( $this->i1 );

		$fingerprint = $this->itemTermStore->getTerms( $this->i1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testStoreTermsCleansUpRemovedTerms() {
		$this->itemTermStore->storeTerms(
			$this->i1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'The real name of UserName is John Doe' ) ] )
			)
		);
		$this->itemTermStore->storeTerms(
			$this->i1,
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
		$this->itemTermStore->storeTerms(
			$this->i1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'The real name of UserName is John Doe' ) ] )
			)
		);
		$this->itemTermStore->deleteTerms( $this->i1 );

		$this->assertSelect(
			'wbt_text',
			'wbx_text',
			[ 'wbx_text' => 'The real name of UserName is John Doe' ],
			[ /* empty */ ]
		);
	}

	public function testStoreTerms_throwsForForeignItemId() {
		$this->expectException( InvalidArgumentException::class );
		$this->itemTermStore->storeTerms( new ItemId( 'wd:P1' ), $this->fingerprintEmpty );
	}

	public function testDeleteTerms_throwsForForeignItemId() {
		$this->expectException( InvalidArgumentException::class );
		$this->itemTermStore->deleteTerms( new ItemId( 'wd:P1' ) );
	}

	public function testGetTerms_throwsForForeignItemId() {
		$this->expectException( InvalidArgumentException::class );
		$this->itemTermStore->getTerms( new ItemId( 'wd:P1' ) );
	}

	public function testStoresAndGetsUTF8Text() {
		$this->fingerprint1->setDescription(
			'utf8',
			'ఒక వ్యక్తి లేదా సంస్థ సాధించిన రికార్డు. ఈ రికార్డును సాధించిన కోల్పోయిన తేదీలను చూపేందుకు క్'
		);

		$this->itemTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->itemTermStore->getTerms( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testT237984UnexpectedMissingTextRow() {
		$propertyTermStore = new DatabasePropertyTermStore(
			$this->loadBalancer,
			new DatabaseTermIdsAcquirer(
				$this->lbFactory,
				$this->typeIdsStore
			),
			new DatabaseTermIdsResolver(
				$this->typeIdsStore,
				$this->typeIdsStore,
				$this->loadBalancer
			),
			new DatabaseTermIdsCleaner(
				$this->loadBalancer
			),
			new StringNormalizer()
		);

		$propertyTermStore->storeTerms( new PropertyId( 'P12' ), new Fingerprint(
			new TermList( [ new Term( 'nl', 'van' ) ] )
		) );
		$this->itemTermStore->storeTerms( new ItemId( 'Q99' ), new Fingerprint(
			new TermList(),
			new TermList( [ new Term( 'af', 'van' ) ] )
		) );

		// Store with empty fingerprint (will delete things)
		$this->itemTermStore->storeTerms( new ItemId( 'Q99' ), new Fingerprint() );

		$r = $propertyTermStore->getTerms( new PropertyId( 'P12' ) );
		$this->assertTrue( $r->hasLabel( 'nl' ) );
		$this->assertEquals( 'van', $r->getLabel( 'nl' )->getText() );
	}

}
