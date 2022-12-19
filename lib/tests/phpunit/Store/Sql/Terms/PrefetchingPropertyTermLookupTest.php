<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use JobQueueGroup;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStoreWriter;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\Tests\Rdbms\LocalRepoDbTestHelper;
use Wikibase\Lib\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PrefetchingPropertyTermLookupTest extends MediaWikiIntegrationTestCase {

	use LocalRepoDbTestHelper;

	/** @var PrefetchingPropertyTermLookup */
	private $lookup;

	/** @var NumericPropertyId */
	private $p1;

	/** @var NumericPropertyId */
	private $p2;

	protected function setUp(): void {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}
		parent::setUp();
		$tables = [
			'wbt_property_terms',
			'wbt_term_in_lang',
			'wbt_text_in_lang',
			'wbt_text',
			'wbt_type',
		];

		$this->tablesUsed = array_merge( $this->tablesUsed, $tables );

		$repoDb = $this->getRepoDomainDb();
		$typeIdsStore = new DatabaseTypeIdsStore(
			$repoDb,
			MediaWikiServices::getInstance()->getMainWANObjectCache()
		);
		$termIdsStore = new DatabaseTermInLangIdsResolver(
			$typeIdsStore,
			$typeIdsStore,
			$repoDb
		);
		$this->lookup = new PrefetchingPropertyTermLookup(
			$termIdsStore
		);

		$propertyTermStoreWriter = new DatabasePropertyTermStoreWriter(
			$repoDb,
			$this->createMock( JobQueueGroup::class ),
			new DatabaseTermInLangIdsAcquirer(
				$repoDb,
				$typeIdsStore
			),
			$termIdsStore,
			new StringNormalizer()
		);
		$this->p1 = new NumericPropertyId( 'P1' );
		$propertyTermStoreWriter->storeTerms(
			$this->p1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'property one' ) ] ),
				new TermList( [ new Term( 'en', 'the first property' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'P1' ] ) ] )
			)
		);
		$this->p2 = new NumericPropertyId( 'P2' );
		$propertyTermStoreWriter->storeTerms(
			$this->p2,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'property two' ) ] ),
				new TermList( [ new Term( 'en', 'the second property' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'P2' ] ) ] )
			)
		);
	}

	public function testGetLabel() {
		$label1 = $this->lookup->getLabel( $this->p1, 'en' );
		$label2 = $this->lookup->getLabel( $this->p2, 'en' );

		$this->assertSame( 'property one', $label1 );
		$this->assertSame( 'property two', $label2 );
	}

	public function testGetDescription() {
		$description1 = $this->lookup->getDescription( $this->p1, 'en' );
		$description2 = $this->lookup->getDescription( $this->p2, 'en' );

		$this->assertSame( 'the first property', $description1 );
		$this->assertSame( 'the second property', $description2 );
	}

	public function testPrefetchTermsAndGetPrefetchedTerm() {
		$this->lookup->prefetchTerms(
			[ $this->p1, $this->p2 ],
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS ],
			[ 'en' ]
		);

		$label1 = $this->lookup->getPrefetchedTerm( $this->p1, TermTypes::TYPE_LABEL, 'en' );
		$this->assertSame( 'property one', $label1 );
		$description2 = $this->lookup->getPrefetchedTerm( $this->p2, TermTypes::TYPE_DESCRIPTION, 'en' );
		$this->assertSame( 'the second property', $description2 );
		$alias1 = $this->lookup->getPrefetchedTerm( $this->p1, TermTypes::TYPE_ALIAS, 'en' );
		$this->assertSame( 'P1', $alias1 );
	}

	public function testGetPrefetchedTerm_notPrefetched() {
		$this->assertNull( $this->lookup->getPrefetchedTerm( $this->p1, TermTypes::TYPE_LABEL, 'en' ) );
	}

	public function testGetPrefetchedTerm_doesNotExist() {
		$this->lookup->prefetchTerms(
			[ $this->p1, $this->p2 ],
			[ TermTypes::TYPE_LABEL ],
			[ 'en', 'de' ]
		);

		$this->assertFalse( $this->lookup->getPrefetchedTerm( $this->p1, TermTypes::TYPE_LABEL, 'de' ) );
	}

	public function testPrefetchTerms_Empty() {
		$this->lookup->prefetchTerms( [], [], [] );
		$this->assertTrue( true ); // no error
	}

	public function testPrefetchTerms_SameTermsTwice() {
		$this->lookup->prefetchTerms(
			[ $this->p1 ],
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS ],
			[ 'en' ]
		);
		$this->lookup->prefetchTerms(
			[ $this->p1 ],
			[ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION, TermTypes::TYPE_ALIAS ],
			[ 'en' ]
		);
		$this->assertTrue( true ); // no error
	}

}
