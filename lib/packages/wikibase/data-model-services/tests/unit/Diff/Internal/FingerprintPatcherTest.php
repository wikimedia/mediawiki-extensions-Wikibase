<?php

namespace Wikibase\DataModel\Services\Tests\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\Internal\FingerprintPatcher;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers Wikibase\DataModel\Services\Diff\Internal\FingerprintPatcher
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class FingerprintPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_fingerprintIsReturnedAsIs() {
		$fingerprint = $this->newSimpleFingerprint();

		$this->assertFingerprintResultsFromPatch( $fingerprint, $fingerprint, new EntityDiff() );
	}

	private function newSimpleFingerprint() {
		$fingerprint = new Fingerprint();

		$fingerprint->setLabel( 'en', 'foo' );
		$fingerprint->setDescription( 'de', 'bar' );
		$fingerprint->setAliasGroup( 'nl', array( 'baz' ) );

		return $fingerprint;
	}

	private function assertFingerprintResultsFromPatch( Fingerprint $expected, Fingerprint $original, EntityDiff $patch ) {
		$this->assertTrue( $expected->equals( $this->getPatchedFingerprint( $original, $patch ) ) );
	}

	private function getPatchedFingerprint( Fingerprint $fingerprint, EntityDiff $patch ) {
		$patched = unserialize( serialize( $fingerprint ) );

		$patcher = new FingerprintPatcher();
		$patcher->patchFingerprint( $patched, $patch );

		return $patched;
	}

	public function testLabelDiffOnlyAffectsLabels() {
		$fingerprint = $this->newSimpleFingerprint();

		$patch = new EntityDiff( array(
			'label' => new Diff( array(
				'en' => new DiffOpChange( 'foo', 'bar' ),
				'de' => new DiffOpAdd( 'baz' ),
			), true )
		) );

		$expectedFingerprint = $this->newSimpleFingerprint();
		$expectedFingerprint->setLabel( 'en', 'bar' );
		$expectedFingerprint->setLabel( 'de', 'baz' );

		$this->assertFingerprintResultsFromPatch( $expectedFingerprint, $fingerprint, $patch );
	}

	public function testDescriptionDiffOnlyAffectsDescriptions() {
		$fingerprint = $this->newSimpleFingerprint();

		$patch = new EntityDiff( array(
			'description' => new Diff( array(
				'de' => new DiffOpChange( 'bar', 'foo' ),
				'en' => new DiffOpAdd( 'baz' ),
			), true )
		) );

		$expectedFingerprint = $this->newSimpleFingerprint();
		$expectedFingerprint->setDescription( 'de', 'foo' );
		$expectedFingerprint->setDescription( 'en', 'baz' );

		$this->assertFingerprintResultsFromPatch( $expectedFingerprint, $fingerprint, $patch );
	}

	public function aliasDiffProvider() {
		return array(
			'diffs containing add/remove ops (default)' => array( array(
				'de' => new Diff( array( new DiffOpAdd( 'foo' ) ), false ),
				'en' => new Diff( array( new DiffOpRemove( 'en-old' ), new DiffOpAdd( 'en-new' ) ), false ),
				'fa' => new Diff( array( new DiffOpRemove( 'fa-old' ) ), false ),
			) ),
			'associative diffs containing atomic ops' => array( array(
				'de' => new Diff( array( new DiffOpAdd( 'foo' ) ), true ),
				'en' => new Diff( array( new DiffOpChange( 'en-old', 'en-new' ) ), true ),
				'fa' => new Diff( array( new DiffOpRemove( 'fa-old' ) ), true ),
			) ),
			'non-associative diffs containing atomic ops' => array( array(
				'de' => new Diff( array( new DiffOpAdd( 'foo' ) ), true ),
				'en' => new Diff( array( new DiffOpChange( 'en-old', 'en-new' ) ), false ),
				'fa' => new Diff( array( new DiffOpRemove( 'fa-old' ) ), true ),
			) ),
			'partly associative diffs (auto-detected) containing atomic ops' => array( array(
				'de' => new Diff( array( new DiffOpAdd( 'foo' ) ) ),
				'en' => new Diff( array( new DiffOpChange( 'en-old', 'en-new' ) ) ),
				'fa' => new Diff( array( new DiffOpRemove( 'fa-old' ) ) ),
			) ),
			'atomic diff ops' => array( array(
				'de' => new DiffOpAdd( array( 'foo' ) ),
				'en' => new DiffOpChange( array( 'en-old' ), array( 'en-new' ) ),
				'fa' => new DiffOpRemove( array( 'fa-old' ) ),
			) ),
		);
	}

	/**
	 * @dataProvider aliasDiffProvider
	 */
	public function testAliasDiffOnlyAffectsAliases( array $diffOps ) {
		$fingerprint = $this->newSimpleFingerprint();
		$fingerprint->setAliasGroup( 'en', array( 'en-old' ) );
		$fingerprint->setAliasGroup( 'fa', array( 'fa-old' ) );

		$patch = new EntityDiff( array(
			'aliases' => new Diff( $diffOps, true ),
		) );

		$expectedFingerprint = $this->newSimpleFingerprint();
		$expectedFingerprint->setAliasGroup( 'de', array( 'foo' ) );
		$expectedFingerprint->setAliasGroup( 'en', array( 'en-new' ) );

		$this->assertFingerprintResultsFromPatch( $expectedFingerprint, $fingerprint, $patch );
	}

	public function conflictingEditProvider() {
		return array(
			'does not add existing label language' => array( array(
				'label' => new Diff( array(
					'en' => new DiffOpAdd( 'added' ),
				), true ),
			) ),
			'does not change modified label' => array( array(
				'label' => new Diff( array(
					'en' => new DiffOpChange( 'original', 'changed' ),
				), true ),
			) ),
			'does not change missing label' => array( array(
				'label' => new Diff( array(
					'de' => new DiffOpChange( 'original', 'changed' ),
				), true ),
			) ),
			'does not remove modified label' => array( array(
				'label' => new Diff( array(
					'en' => new DiffOpRemove( 'original' ),
				), true ),
			) ),
			'removing missing label is no-op' => array( array(
				'label' => new Diff( array(
					'de' => new DiffOpRemove( 'original' ),
				), true ),
			) ),

			'does not add existing description language' => array( array(
				'description' => new Diff( array(
					'en' => new DiffOpAdd( 'added' ),
				), true ),
			) ),
			'does not change modified description' => array( array(
				'description' => new Diff( array(
					'en' => new DiffOpChange( 'original', 'changed' ),
				), true ),
			) ),
			'changing missing description is no-op' => array( array(
				'description' => new Diff( array(
					'de' => new DiffOpChange( 'original', 'changed' ),
				), true ),
			) ),
			'does not remove modified description' => array( array(
				'description' => new Diff( array(
					'en' => new DiffOpRemove( 'original' ),
				), true ),
			) ),
			'removing missing description is no-op' => array( array(
				'description' => new Diff( array(
					'de' => new DiffOpRemove( 'original' ),
				), true ),
			) ),

			'does not add existing aliases language' => array( array(
				'aliases' => new Diff( array(
					'en' => new DiffOpAdd( array( 'added' ) ),
				), true ),
			) ),
			'does not change missing aliases language' => array( array(
				'aliases' => new Diff( array(
					'de' => new Diff( array( new DiffOpRemove( 'original' ), new DiffOpAdd( 'changed' ) ) ),
				), true ),
			) ),
			'changing missing aliases is no-op' => array( array(
				'aliases' => new Diff( array(
					'de' => new Diff( array( new DiffOpChange( 'original', 'changed' ) ), true ),
					'en' => new Diff( array( new DiffOpChange( 'original', 'changed' ) ), true ),
				), true ),
			) ),
			'changing missing aliases is no-op (atomic)' => array( array(
				'aliases' => new Diff( array(
					'de' => new DiffOpChange( array( 'original' ), array( 'changed' ) ),
					'en' => new DiffOpChange( array( 'original' ), array( 'changed' ) ),
				), true ),
			) ),
			'removing missing aliases is no-op' => array( array(
				'aliases' => new Diff( array(
					'de' => new Diff( array( new DiffOpRemove( 'original' ) ) ),
					'en' => new Diff( array( new DiffOpRemove( 'original' ) ) ),
				), true ),
			) ),
			'removing missing aliases is no-op (atomic)' => array( array(
				'aliases' => new Diff( array(
					'de' => new DiffOpRemove( array( 'original' ) ),
					'en' => new DiffOpRemove( array( 'original' ) ),
				), true ),
			) ),
		);
	}

	/**
	 * @dataProvider conflictingEditProvider
	 */
	public function testGivenConflictingEdit_fingerprintIsReturnedAsIs( array $diffOps ) {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'conflict' );
		$fingerprint->setDescription( 'en', 'conflict' );
		$fingerprint->setAliasGroup( 'en', array( 'conflict' ) );

		$patch = new EntityDiff( $diffOps );

		$this->assertFingerprintResultsFromPatch( $fingerprint, $fingerprint, $patch );
	}

}
