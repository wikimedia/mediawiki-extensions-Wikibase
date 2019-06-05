<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use MediaWikiTestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\Terms\DatabasePropertyTermStore;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsCleaner;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\InMemoryTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PropertyTermLookup;
use Wikibase\StringNormalizer;
use Wikibase\TermStore\MediaWiki\Tests\Util\FakeLoadBalancer;
use Wikimedia\Rdbms\IMaintainableDatabase;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\PropertyTermLookup
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PropertyTermLookupTest extends MediaWikiTestCase {

	/** @var PropertyTermLookup */
	private $propertyTermLookup;

	/** @var PropertyId */
	private $p1;

	/** @var PropertyId */
	private $p2;

	protected function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_property_terms';
		$loadBalancer = new FakeLoadBalancer( [ 'dbr' => $this->db ] );
		$typeIdsStore = new InMemoryTypeIdsStore();
		$termIdsResolver = new DatabaseTermIdsResolver(
			$typeIdsStore,
			$loadBalancer
		);
		$this->propertyTermLookup = new PropertyTermLookup(
			$loadBalancer,
			$termIdsResolver
		);

		$propertyTermStore = new DatabasePropertyTermStore(
			$loadBalancer,
			new DatabaseTermIdsAcquirer(
				$loadBalancer,
				$typeIdsStore
			),
			$termIdsResolver,
			new DatabaseTermIdsCleaner(
				$loadBalancer
			),
			new StringNormalizer()
		);
		$this->p1 = new PropertyId( 'P1' );
		$propertyTermStore->storeTerms(
			$this->p1,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'property one' ) ] ),
				new TermList( [ new Term( 'en', 'the first property' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'P1' ] ) ] )
			)
		);
		$this->p2 = new PropertyId( 'P2' );
		$propertyTermStore->storeTerms(
			$this->p2,
			new Fingerprint(
				new TermList( [ new Term( 'en', 'property two' ) ] ),
				new TermList( [ new Term( 'en', 'the second property' ) ] ),
				new AliasGroupList( [ new AliasGroup( 'en', [ 'P2' ] ) ] )
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
		$label1 = $this->propertyTermLookup->getLabel( $this->p1, 'en' );
		$label2 = $this->propertyTermLookup->getLabel( $this->p2, 'en' );

		$this->assertSame( 'property one', $label1 );
		$this->assertSame( 'property two', $label2 );
	}

	public function testGetDescription() {
		$description1 = $this->propertyTermLookup->getDescription( $this->p1, 'en' );
		$description2 = $this->propertyTermLookup->getDescription( $this->p2, 'en' );

		$this->assertSame( 'the first property', $description1 );
		$this->assertSame( 'the second property', $description2 );
	}

	public function testPrefetchTermsAndGetPrefetchedTerm() {
		$this->propertyTermLookup->prefetchTerms(
			[ $this->p1, $this->p2 ],
			[ 'label', 'description', 'alias' ],
			[ 'en' ]
		);

		$label1 = $this->propertyTermLookup->getPrefetchedTerm( $this->p1, 'label', 'en' );
		$this->assertSame( 'property one', $label1 );
		$description2 = $this->propertyTermLookup->getPrefetchedTerm( $this->p2, 'description', 'en' );
		$this->assertSame( 'the second property', $description2 );
		$alias1 = $this->propertyTermLookup->getPrefetchedTerm( $this->p1, 'alias', 'en' );
		$this->assertSame( 'P1', $alias1 );
	}

	public function testGetPrefetchedTerm_notPrefetched() {
		$this->assertNull( $this->propertyTermLookup->getPrefetchedTerm( $this->p1, 'label', 'en' ) );
	}

	public function testGetPrefetchedTerm_doesNotExist() {
		$this->propertyTermLookup->prefetchTerms(
			[ $this->p1, $this->p2 ],
			[ 'label' ],
			[ 'en', 'de' ]
		);

		$this->assertFalse( $this->propertyTermLookup->getPrefetchedTerm( $this->p1, 'label', 'de' ) );
	}

}
