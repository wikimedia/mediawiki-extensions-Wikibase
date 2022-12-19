<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use PHPUnit\Framework\TestCase;
use Wikibase\Lib\Store\Sql\Terms\InMemoryTermStore;

/**
 * @covers \Wikibase\Lib\Store\Sql\Terms\InMemoryTermStore
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class InMemoryTermStoreTest extends TestCase {

	public function testAcquiresUniqueIdsForNonExistingTerms() {
		$termsIdsAcquirer = new InMemoryTermStore();

		$ids = $termsIdsAcquirer->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
			'alias' => [
				'en' => [ 'alias', 'another alias' ],
				'de' => 'de alias',
			],
		] );

		$this->assertNoDuplicates( $ids );
	}

	public function testReusesIdsOfExistingTerms() {
		$termsIdsAcquirer = new InMemoryTermStore();

		$previouslyAcquiredIds = $termsIdsAcquirer->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
			'alias' => [
				'en' => [ 'alias', 'another alias' ],
				'de' => 'de alias',
			],
		] );

		$newlyAcquiredIds = $termsIdsAcquirer->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
			'alias' => [
				'en' => [ 'alias', 'another alias' ],
				'de' => 'de alias',
			],
		] );

		$this->assertEquals(
			$previouslyAcquiredIds,
			$newlyAcquiredIds
		);
	}

	public function testAcquiresAndReusesIdsForNewAndExistingTerms() {
		$termsIdsAcquirer = new InMemoryTermStore();

		$previouslyAcquiredIds = $termsIdsAcquirer->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
		] );

		$newlyAcquiredIds = $termsIdsAcquirer->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'another label',
			],
			'alias' => [
				'en' => [ 'alias', 'another alias' ],
				'de' => 'de alias',
			],
		] );

		$this->assertNoDuplicates( $newlyAcquiredIds );

		$this->assertEquals(
			$previouslyAcquiredIds,
			array_intersect( $previouslyAcquiredIds, $newlyAcquiredIds )
		);
	}

	private function assertNoDuplicates( $ids ) {
		$this->assertSame(
			count( $ids ),
			count( array_unique( $ids ) )
		);
	}

	public function testResolveTermIds_returnsAcquiredTerms_butNotAllTerms() {
		$termIdsStore = new InMemoryTermStore();

		$termIds1 = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'eine Beschriftung',
			],
		] );
		$termIds2 = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'de' => 'eine Beschriftung',
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		] );

		$terms1 = $termIdsStore->resolveTermInLangIds( $termIds1 );
		$terms2 = $termIdsStore->resolveTermInLangIds( $termIds2 );

		$this->assertSame( [
			'label' => [
				'en' => [ 'some label' ],
				'de' => [ 'eine Beschriftung' ],
			],
		], $terms1 );
		$this->assertSame( [
			'label' => [
				'de' => [ 'eine Beschriftung' ],
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		], $terms2 );
	}

	public function testResolveTermIds_filterLanguages() {
		$termIdsStore = new InMemoryTermStore();

		$termIds = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'eine Beschriftung',
			],
		] );

		$terms = $termIdsStore->resolveTermInLangIds( $termIds, null, [ 'en' ] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'some label' ],
			],
		], $terms );
	}

	public function testResolveTermIds_filterGroups() {
		$termIdsStore = new InMemoryTermStore();

		$termIds = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'eine Beschriftung',
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		] );

		$terms = $termIdsStore->resolveTermInLangIds( $termIds, [ 'alias' ] );

		$this->assertSame( [
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		], $terms );
	}

	public function testResolveTermIds_filterGroupsAndLanguages() {
		$termIdsStore = new InMemoryTermStore();

		$termIds = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'eine Beschriftung',
			],
			'description' => [
				'de' => 'Beschreibung',
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
				'es' => [ 'foo' ],
			],
		] );

		$terms = $termIdsStore->resolveTermInLangIds( $termIds, [ 'label', 'alias' ], [ 'en', 'es' ] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'some label' ],
			],
			'alias' => [
				'es' => [ 'foo' ],
			],
		], $terms );
	}

	public function testResolveGroupedTermIds_returnsCorrectGroups() {
		$termIdsStore = new InMemoryTermStore();

		$termIds1 = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'eine Beschriftung',
			],
		] );
		$termIds2 = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'de' => 'eine Beschriftung',
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		] );

		$terms = $termIdsStore->resolveGroupedTermInLangIds( [
			'terms1' => $termIds1,
			'terms2' => $termIds2,
		] );

		$this->assertSame( [
			'label' => [
				'en' => [ 'some label' ],
				'de' => [ 'eine Beschriftung' ],
			],
		], $terms['terms1'] );
		$this->assertSame( [
			'label' => [
				'de' => [ 'eine Beschriftung' ],
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		], $terms['terms2'] );
	}

	public function testResolveGroupedTermIds_filterGroupsAndLanguages() {
		$termIdsStore = new InMemoryTermStore();

		$termIds1 = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'en' => 'some label',
				'de' => 'eine Beschriftung',
			],
		] );
		$termIds2 = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'de' => 'eine Beschriftung',
			],
			'alias' => [
				'de' => [ 'ein Alias', 'noch ein Alias' ],
			],
		] );

		$terms = $termIdsStore->resolveGroupedTermInLangIds( [
			'terms1' => $termIds1,
			'terms2' => $termIds2,
		], [ 'label' ], [ 'de' ] );

		$this->assertSame( [
			'label' => [
				'de' => [ 'eine Beschriftung' ],
			],
		], $terms['terms1'] );
		$this->assertSame( [
			'label' => [
				'de' => [ 'eine Beschriftung' ],
			],
		], $terms['terms2'] );
	}

	public function testCleanTermIds_doesNotReuseIds() {
		$termIdsStore = new InMemoryTermStore();

		$originalIds = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'en' => 'the label',
				'de' => 'die Bezeichnung',
			],
			'alias' => [
				'en' => [ 'alias', 'another' ],
			],
			'description' => [ 'en' => 'the description' ],
		] );

		$termIdsStore->cleanTermInLangIds( $originalIds );

		$newIds = $termIdsStore->acquireTermInLangIds( [
			'label' => [ 'en' => 'the label' ],
			'description' => [ 'en' => 'the description' ],
		] );

		// Assert that the lowest new id is higher than the highest original id
		$this->assertGreaterThan( max( $originalIds ), min( $newIds ) );
	}

	public function testCleanTermIds_keepsOtherIds() {
		$termIdsStore = new InMemoryTermStore();

		$acquiredIds = $termIdsStore->acquireTermInLangIds( [
			'label' => [
				'en' => 'id 1',
				'de' => 'id 2',
			],
			'alias' => [
				'en' => [ 'id 3' ],
			],
			'description' => [ 'en' => 'id 4' ],
		] );

		$termIdsStore->cleanTermInLangIds( [ $acquiredIds[1], $acquiredIds[2] ] );

		$this->assertSame(
			[ $acquiredIds[0], $acquiredIds[3] ],
			$termIdsStore->acquireTermInLangIds( [
				'label' => [ 'en' => 'id 1' ],
				'description' => [ 'en' => 'id 4' ],
			] )
		);
	}

}
