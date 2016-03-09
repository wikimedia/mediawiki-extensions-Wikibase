<?php

namespace Wikibase\DataModel\Services\Tests\Diff;

use RuntimeException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;

/**
 * @covers Wikibase\DataModel\Services\Diff\EntityDiff
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 */
abstract class EntityDiffOldTest extends \PHPUnit_Framework_TestCase {

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

	public static function generateApplyData( $entityType ) {
		$tests = array();

		// #0: add label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setLabel( 'en', 'Test' );
		$b->setLabel( 'de', 'Test' );

		$tests[] = array( $a, $b );

		// #1: remove label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );
		$a->setLabel( 'de', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setLabel( 'de', 'Test' );

		$tests[] = array( $a, $b );

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

		$tests[] = array( $a, $b );

		// #4: remove description
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );
		$a->setDescription( 'de', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setDescription( 'de', 'Test' );

		$tests[] = array( $a, $b );

		// #5: change description
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );

		$b = self::newEntity( $entityType );
		$b->setDescription( 'en', 'Test!!!' );

		$tests[] = array( $a, $b );

		// #6: add alias ------------------------------
		$a = self::newEntity( $entityType );
		$a->setAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = self::newEntity( $entityType );
		$b->setAliases( 'en', array( 'Foo', 'Bar', 'Quux' ) );

		$tests[] = array( $a, $b );

		// #7: add alias language
		$a = self::newEntity( $entityType );
		$a->setAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = self::newEntity( $entityType );
		$b->setAliases( 'en', array( 'Foo', 'Bar' ) );
		$b->setAliases( 'de', array( 'Quux' ) );

		$tests[] = array( $a, $b );

		// #8: remove alias
		$a = self::newEntity( $entityType );
		$a->setAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = self::newEntity( $entityType );
		$b->setAliases( 'en', array( 'Bar' ) );

		$tests[] = array( $a, $b );

		// #9: remove alias language
		$a = self::newEntity( $entityType );
		$a->setAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = self::newEntity( $entityType );

		$tests[] = array( $a, $b );
		return $tests;
	}

	/**
	 * @return array[]
	 */
	public abstract function provideApplyData();

	/**
	 * @dataProvider provideApplyData
	 */
	public function testApply( Item $a, Item $b ) {
		$differ = new EntityDiffer();
		$patcher = new EntityPatcher();

		$patcher->patchEntity( $a, $differ->diffEntities( $a, $b ) );
		$this->assertTrue( $a->getFingerprint()->equals( $b->getFingerprint() ) );
	}

	public function provideConflictDetection() {
		$cases = array();

		// #0: adding a label where there was none before
		$base = self::newEntity( Item::ENTITY_TYPE );
		$current = unserialize( serialize( $base ) );

		$new = unserialize( serialize( $base ) );
		$new->setLabel( 'en', 'TEST' );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should eb no conflicts.
		);

		// #1: adding an alias where there was none before
		$base = self::newEntity( Item::ENTITY_TYPE );
		$current = $base;

		$new = unserialize( serialize( $base ) );
		$new->setAliases( 'en', array( 'TEST' ) );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should eb no conflicts.
		);

		// #2: adding an alias where there already was one before
		$base = self::newEntity( Item::ENTITY_TYPE );
		$base->setAliases( 'en', array( 'Foo' ) );
		$current = $base;

		$new = unserialize( serialize( $base ) );
		$new->setAliases( 'en', array( 'Bar' ) );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should be no conflicts.
		);

		// #3: adding an alias where there already was one in another language
		$base = self::newEntity( Item::ENTITY_TYPE );
		$base->setAliases( 'en', array( 'Foo' ) );
		$current = $base;

		$new = unserialize( serialize( $base ) );
		$new->setAliases( 'de', array( 'Bar' ) );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should be no conflicts.
		);

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
