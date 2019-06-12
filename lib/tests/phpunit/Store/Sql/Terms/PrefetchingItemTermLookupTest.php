<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStore;
use Wikibase\Lib\Store\Sql\Terms\InMemoryTermIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\StringNormalizer;
use Wikibase\TermStore\MediaWiki\Tests\Util\FakeLoadBalancer;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingItemTermLookupTest extends MediaWikiTestCase {

	/** @var PrefetchingPropertyTermLookup */
	private $lookup;

	/** @var ItemId */
	private $i1;

	/** @var ItemId */
	private $i2;

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'wbt_item_terms';
		$loadBalancer = new FakeLoadBalancer( [ 'dbr' => $this->db ] );
		$termIdsStore = new InMemoryTermIdsStore();
		$this->lookup = new PrefetchingItemTermLookup(
			$loadBalancer,
			$termIdsStore
		);

		$itemTermStore = new DatabaseItemTermStore(
			$loadBalancer,
			$termIdsStore,
			$termIdsStore,
			$termIdsStore,
			new StringNormalizer()
		);
		$this->i1 = new ItemId( 'Q1' );
		$itemTermStore->storeTerms(
			$this->i1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'property one' ) ] ),
				new TermList( [ new Term( 'en', 'the first property' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'Q1' ] ) ] )
			)
		);
		$this->i2 = new ItemId( 'Q2' );
		$itemTermStore->storeTerms(
			$this->i2,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'property two' ) ] ),
				new TermList( [ new Term( 'en', 'the second property' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'Q2' ] ) ] )
			)
		);
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

	public function testGetLabel() {
		$label1 = $this->lookup->getLabel( $this->i1, 'en' );
		$label2 = $this->lookup->getLabel( $this->i2, 'en' );

		$this->assertSame( 'property one', $label1 );
		$this->assertSame( 'property two', $label2 );
	}

	public function testGetDescription() {
		$description1 = $this->lookup->getDescription( $this->i1, 'en' );
		$description2 = $this->lookup->getDescription( $this->i2, 'en' );

		$this->assertSame( 'the first property', $description1 );
		$this->assertSame( 'the second property', $description2 );
	}

	public function testPrefetchTermsAndGetPrefetchedTerm() {
		$this->lookup->prefetchTerms(
			[ $this->i1, $this->i2 ],
			[ 'label', 'description', 'alias' ],
			[ 'en' ]
		);

		$label1 = $this->lookup->getPrefetchedTerm( $this->i1, 'label', 'en' );
		$this->assertSame( 'property one', $label1 );
		$description2 = $this->lookup->getPrefetchedTerm( $this->i2, 'description', 'en' );
		$this->assertSame( 'the second property', $description2 );
		$alias1 = $this->lookup->getPrefetchedTerm( $this->i1, 'alias', 'en' );
		$this->assertSame( 'Q1', $alias1 );
	}

	public function testGetPrefetchedTerm_notPrefetched() {
		$this->assertNull( $this->lookup->getPrefetchedTerm( $this->i1, 'label', 'en' ) );
	}

	public function testGetPrefetchedTerm_doesNotExist() {
		$this->lookup->prefetchTerms(
			[ $this->i1, $this->i2 ],
			[ 'label' ],
			[ 'en', 'de' ]
		);

		$this->assertFalse( $this->lookup->getPrefetchedTerm( $this->i1, 'label', 'de' ) );
	}

	public function testPrefetchTerms_Empty() {
		$this->lookup->prefetchTerms( [] );
		$this->assertTrue( true ); // no error
	}

	public function testPrefetchTerms_SameTermsTwice() {
		$this->lookup->prefetchTerms( [ $this->i1 ] );
		$this->lookup->prefetchTerms( [ $this->i1 ] );
		$this->assertTrue( true ); // no error
	}

}
