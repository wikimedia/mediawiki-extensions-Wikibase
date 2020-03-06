<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\DatabaseItemTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Tests\Store\Sql\Terms\Util\MockJobQueueFactory;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingItemTermLookupTest extends MediaWikiIntegrationTestCase {

	/** @var PrefetchingItemTermLookup */
	private $lookup;

	/** @var ItemId */
	private $i1;

	/** @var ItemId */
	private $i2;

	protected function setUp() : void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		parent::setUp();
		$tables = [
			'wbt_item_terms',
			'wbt_term_in_lang',
			'wbt_text_in_lang',
			'wbt_text',
			'wbt_type'
		];

		$this->tablesUsed = array_merge( $this->tablesUsed, $tables );

		$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancer();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$loadBalancer,
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);
		$termIdsStore = new DatabaseTermInLangIdsResolver(
			$typeIdsStore,
			$typeIdsStore,
			$loadBalancer
		);
		$this->lookup = new PrefetchingItemTermLookup(
			$loadBalancer,
			$termIdsStore
		);

		$itemTermStoreWriter = new DatabaseItemTermStoreWriter(
			$loadBalancer,
			( new MockJobQueueFactory( $this ) )->getMockJobQueue(),
			new DatabaseTermInLangIdsAcquirer(
				MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
				$typeIdsStore
			),
			$termIdsStore,
			new StringNormalizer(),
			$this->getItemSource()
		);
		$this->i1 = new ItemId( 'Q1' );
		$itemTermStoreWriter->storeTerms(
			$this->i1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'property one' ) ] ),
				new TermList( [ new Term( 'en', 'the first property' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'Q1' ] ) ] )
			)
		);
		$this->i2 = new ItemId( 'Q2' );
		$itemTermStoreWriter->storeTerms(
			$this->i2,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'property two' ) ] ),
				new TermList( [ new Term( 'en', 'the second property' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'Q2' ] ) ] )
			)
		);
	}

	private function getItemSource() {
		return new EntitySource( 'test', false, [ 'item' => [ 'namespaceId' => 10, 'slot' => 'main' ] ], '', '', '', '' );
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
		$this->lookup->prefetchTerms( [], [], [] );
		$this->assertTrue( true ); // no error
	}

	public function testPrefetchTerms_SameTermsTwice() {
		$this->lookup->prefetchTerms( [ $this->i1 ], [ 'label', 'description', 'alias' ], [ 'en' ] );
		$this->lookup->prefetchTerms( [ $this->i1 ], [ 'label', 'description', 'alias' ], [ 'en' ] );
		$this->assertTrue( true ); // no error
	}

}
