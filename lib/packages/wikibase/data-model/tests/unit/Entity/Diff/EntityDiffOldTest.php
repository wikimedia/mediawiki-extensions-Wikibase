<?php

namespace Wikibase\Test\Entity\Diff;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;

/**
 * @covers Wikibase\DataModel\Entity\Diff\EntityDiff
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseDiff
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jens Ohlig <jens.ohlig@wikimedia.de>
 */
abstract class EntityDiffOldTest extends \PHPUnit_Framework_TestCase {

	private static function newEntity ( $entityType ) {
		switch ( $entityType ) {
			case Item::ENTITY_TYPE:
				$entity = Item::newEmpty();
				break;
			case Property::ENTITY_TYPE:
				$entity = Property::newFromType( 'string' );
				break;
			default:
				throw new \RuntimeException( "unknown entity type: $entityType" );
		}

		return $entity;
	}

	public static function generateApplyData( $entityType ) {
		$tests = array();

		// #0: add label
		$a = self::newEntity( $entityType );
		$a->setLabel( 'en', 'Test' );

		$b = $a->copy();
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

		$b = $a->copy();
		$b->setLabel( 'en', 'Test!!!' );

		// #3: add description ------------------------------
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );

		$b = $a->copy();
		$b->setDescription( 'de', 'Test' );

		$tests[] = array( $a, $b );

		// #4: remove description
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );
		$a->setDescription( 'de', 'Test' );

		$b = $a->copy();
		$b->removeDescription( 'en' );

		$tests[] = array( $a, $b );

		// #5: change description
		$a = self::newEntity( $entityType );
		$a->setDescription( 'en', 'Test' );

		$b = $a->copy();
		$b->setDescription( 'en', 'Test!!!' );

		$tests[] = array( $a, $b );

		// #6: add alias ------------------------------
		$a = self::newEntity( $entityType );
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->addAliases( 'en', array( 'Quux' ) );

		$tests[] = array( $a, $b );

		// #7: add alias language
		$a = self::newEntity( $entityType );
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->addAliases( 'de', array( 'Quux' ) );

		$tests[] = array( $a, $b );

		// #8: remove alias
		$a = self::newEntity( $entityType );
		$a->addAliases( 'en', array( 'Foo', 'Bar' ) );

		$b = $a->copy();
		$b->removeAliases( 'en', array( 'Foo' ) );

		$tests[] = array( $a, $b );

		// #9: remove alias language
		$a = self::newEntity( $entityType );

		$b = $a->copy();
		$b->addAliases( 'en', array( 'Foo', 'Bar' ) );
		$b->removeAliases( 'en', array( 'Foo', 'Bar' ) );

		$tests[] = array( $a, $b );
		return $tests;
	}

	/**
	 *
	 * @dataProvider provideApplyData
	 */
	public function testApply( Entity $a, Entity $b ) {
		$a->patch( $a->getDiff( $b ) );
		$this->assertTrue( $a->getFingerprint()->equals( $b->getFingerprint() ) );
	}

	public static function provideConflictDetection() {
		$cases = array();

		// #0: adding a label where there was none before
		$base = self::newEntity( Item::ENTITY_TYPE );
		$current = $base->copy();

		$new = $base->copy();
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

		$new = $base->copy();
		$new->addAliases( 'en', array( 'TEST' ) );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should eb no conflicts.
		);

		// #2: adding an alias where there already was one before
		$base = self::newEntity( Item::ENTITY_TYPE );
		$base->addAliases( 'en', array( 'Foo' ) );
		$current = $base;

		$new = $base->copy();
		$new->addAliases( 'en', array( 'Bar' ) );

		$cases[] = array(
			$base,
			$current,
			$new,
			0 // there should be no conflicts.
		);

		// #3: adding an alias where there already was one in another language
		$base = self::newEntity( Item::ENTITY_TYPE );
		$base->addAliases( 'en', array( 'Foo' ) );
		$current = $base;

		$new = $base->copy();
		$new->addAliases( 'de', array( 'Bar' ) );

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
	public function testConflictDetection( Entity $base, Entity $current, Entity $new, $expectedConflicts ) {
		$patch = $base->getDiff( $new );

		$patchedCurrent = $current->copy();
		$patchedCurrent->patch( $patch );

		$cleanPatch = $base->getDiff( $patchedCurrent );

		$conflicts = $patch->count() - $cleanPatch->count();

		$this->assertEquals( $expectedConflicts, $conflicts, "check number of conflicts detected" );
	}

}
