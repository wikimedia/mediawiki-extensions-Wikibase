<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;

/**
 * @covers \Wikibase\DataModel\Services\Diff\EntityDiff
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 */
abstract class EntityDiffOldTest extends TestCase {

	private static function newEntity( $entityType ) {
		switch ( $entityType ) {
			case Item::ENTITY_TYPE:
				return new Item();
			case Property::ENTITY_TYPE:
				return Property::newFromType( 'string' );
			default:
				throw new RuntimeException( "unknown entity type: $entityType" );
		}
	}

	protected function generateApplyData( $entityType ) {
		$tests = [];

		// #0: add label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setLabel( 'en', 'Test' );
		$b->setLabel( 'de', 'Test' );

		$tests[] = [ $a, $b ];

		// #1: remove label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );
		$a->setLabel( 'de', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setLabel( 'de', 'Test' );

		$tests[] = [ $a, $b ];

		// #2: change label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setLabel( 'en', 'Test!!!' );

		// #3: add description ------------------------------
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setDescription( 'en', 'Test' );
		$b->setDescription( 'de', 'Test' );

		$tests[] = [ $a, $b ];

		// #4: remove description
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );
		$a->setDescription( 'de', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setDescription( 'de', 'Test' );

		$tests[] = [ $a, $b ];

		// #5: change description
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setDescription( 'en', 'Test!!!' );

		$tests[] = [ $a, $b ];

		// #6: add alias ------------------------------
		$a = self::newEntity( $entityType );
		$a->setAliases( 'en', [ 'Foo', 'Bar' ] );

		$b = self::newEntity( $entityType );
		$b->setAliases( 'en', [ 'Foo', 'Bar', 'Quux' ] );

		$tests[] = [ $a, $b ];

		// #7: add alias language
		$a = self::newEntity( $entityType );
		$a->setAliases( 'en', [ 'Foo', 'Bar' ] );

		$b = self::newEntity( $entityType );
		$b->setAliases( 'en', [ 'Foo', 'Bar' ] );
		$b->setAliases( 'de', [ 'Quux' ] );

		$tests[] = [ $a, $b ];

		// #8: remove alias
		$a = self::newEntity( $entityType );
		$a->setAliases( 'en', [ 'Foo', 'Bar' ] );

		$b = self::newEntity( $entityType );
		$b->setAliases( 'en', [ 'Bar' ] );

		$tests[] = [ $a, $b ];

		// #9: remove alias language
		$a = self::newEntity( $entityType );
		$a->setAliases( 'en', [ 'Foo', 'Bar' ] );

		$b = self::newEntity( $entityType );

		$tests[] = [ $a, $b ];
		return $tests;
	}

	public function provideConflictDetection() {
		$cases = [];

		// #0: adding a label where there was none before
		$base = self::newEntity( Item::ENTITY_TYPE );
		$current = unserialize( serialize( $base ) );

		$new = unserialize( serialize( $base ) );
		$new->setLabel( 'en', 'TEST' );

		$cases[] = [
			$base,
			$current,
			$new,
			0, // there should eb no conflicts.
		];

		// #1: adding an alias where there was none before
		$base = self::newEntity( Item::ENTITY_TYPE );
		$current = $base;

		$new = unserialize( serialize( $base ) );
		$new->setAliases( 'en', [ 'TEST' ] );

		$cases[] = [
			$base,
			$current,
			$new,
			0, // there should eb no conflicts.
		];

		// #2: adding an alias where there already was one before
		$base = self::newEntity( Item::ENTITY_TYPE );
		$base->setAliases( 'en', [ 'Foo' ] );
		$current = $base;

		$new = unserialize( serialize( $base ) );
		$new->setAliases( 'en', [ 'Bar' ] );

		$cases[] = [
			$base,
			$current,
			$new,
			0, // there should be no conflicts.
		];

		// #3: adding an alias where there already was one in another language
		$base = self::newEntity( Item::ENTITY_TYPE );
		$base->setAliases( 'en', [ 'Foo' ] );
		$current = $base;

		$new = unserialize( serialize( $base ) );
		$new->setAliases( 'de', [ 'Bar' ] );

		$cases[] = [
			$base,
			$current,
			$new,
			0, // there should be no conflicts.
		];

		return $cases;
	}

	/**
	 * @dataProvider provideConflictDetection
	 */
	public function testConflictDetection(
		EntityDocument $base,
		EntityDocument $current,
		EntityDocument $new,
		$expectedConflicts
	) {
		$differ = new EntityDiffer();
		$patcher = new EntityPatcher();

		$patch = $differ->diffEntities( $base, $new );

		$patchedCurrent = unserialize( serialize( $current ) );
		$patcher->patchEntity( $patchedCurrent, $patch );

		$cleanPatch = $differ->diffEntities( $base, $patchedCurrent );

		$conflicts = $patch->count() - $cleanPatch->count();

		$this->assertEquals( $expectedConflicts, $conflicts, 'check number of conflicts detected' );
	}

}
