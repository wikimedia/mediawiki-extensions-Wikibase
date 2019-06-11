<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use InvalidArgumentException;
use MediaWikiTestCase;
use WANObjectCache;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStore;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\StringNormalizer;
use Wikibase\TermStore\MediaWiki\Tests\Util\FakeLoadBalancer;
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
	private $propertyTermStore;

	/** @var ItemId */
	private $i1;

	/** @var Fingerprint */
	private $fingerprint1;

	/** @var Fingerprint */
	private $fingerprint2;

	/** @var Fingerprint */
	private $fingerprintEmpty;

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'wbt_type';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_item_terms';

		$loadBalancer = new FakeLoadBalancer( [
			'dbr' => $this->db,
		] );
		$typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			WANObjectCache::newEmpty()
		);
		$this->propertyTermStore = new DatabaseItemTermStore(
			$loadBalancer,
			new DatabaseTermIdsAcquirer(
				$loadBalancer,
				$typeIdsStore
			),
			new DatabaseTermIdsResolver(
				$typeIdsStore,
				$loadBalancer
			),
			new DatabaseTermIdsCleaner(
				$loadBalancer
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
		$this->propertyTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->propertyTermStore->getTerms( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testGetTermsWithoutStore() {
		$fingerprint = $this->propertyTermStore->getTerms( $this->i1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testStoreEmptyAndGetTerms() {
		$this->propertyTermStore->storeTerms(
			$this->i1,
			$this->fingerprintEmpty
		);

		$fingerprint = $this->propertyTermStore->getTerms( $this->i1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testDeleteTermsWithoutStore() {
		$this->propertyTermStore->deleteTerms( $this->i1 );
		$this->assertTrue( true, 'did not throw an error' );
	}

	public function testStoreSameFingerprintTwiceAndGetTerms() {
		$this->propertyTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);
		$this->propertyTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->propertyTermStore->getTerms( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

	public function testStoreTwoFingerprintsAndGetTerms() {
		$this->propertyTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);
		$this->propertyTermStore->storeTerms(
			$this->i1,
			$this->fingerprint2
		);

		$fingerprint = $this->propertyTermStore->getTerms( $this->i1 );

		$this->assertEquals( $this->fingerprint2, $fingerprint );
	}

	public function testStoreAndDeleteAndGetTerms() {
		$this->propertyTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$this->propertyTermStore->deleteTerms( $this->i1 );

		$fingerprint = $this->propertyTermStore->getTerms( $this->i1 );

		$this->assertTrue( $fingerprint->isEmpty() );
	}

	public function testStoreTermsCleansUpRemovedTerms() {
		$this->propertyTermStore->storeTerms(
			$this->i1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'The real name of UserName is John Doe' ) ] )
			)
		);
		$this->propertyTermStore->storeTerms(
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
		$this->propertyTermStore->storeTerms(
			$this->i1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'The real name of UserName is John Doe' ) ] )
			)
		);
		$this->propertyTermStore->deleteTerms( $this->i1 );

		$this->assertSelect(
			'wbt_text',
			'wbx_text',
			[ 'wbx_text' => 'The real name of UserName is John Doe' ],
			[ /* empty */ ]
		);
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testStoreTerms_throwsForForeignItemId() {
		$this->propertyTermStore->storeTerms( new ItemId( 'wd:P1' ), $this->fingerprintEmpty );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testDeleteTerms_throwsForForeignItemId() {
		$this->propertyTermStore->deleteTerms( new ItemId( 'wd:P1' ) );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testGetTerms_throwsForForeignItemId() {
		$this->propertyTermStore->getTerms( new ItemId( 'wd:P1' ) );
	}

	public function testStoresAndGetsUTF8Text() {
		$this->fingerprint1->setDescription(
			'utf8',
			'ఒక వ్యక్తి లేదా సంస్థ సాధించిన రికార్డు. ఈ రికార్డును సాధించిన కోల్పోయిన తేదీలను చూపేందుకు క్'
		);

		$this->propertyTermStore->storeTerms(
			$this->i1,
			$this->fingerprint1
		);

		$fingerprint = $this->propertyTermStore->getTerms( $this->i1 );

		$this->assertEquals( $this->fingerprint1, $fingerprint );
	}

}
