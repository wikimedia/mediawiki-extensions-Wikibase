<?php

namespace Wikibase\DataModel\Services\Tests\Diff\Internal;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\Internal\FingerprintPatcher;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers \Wikibase\DataModel\Services\Diff\Internal\FingerprintPatcher
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class FingerprintPatcherTest extends TestCase {

	public function testGivenEmptyDiff_fingerprintIsReturnedAsIs() {
		$fingerprint = $this->newSimpleFingerprint();

		$this->assertFingerprintResultsFromPatch( $fingerprint, $fingerprint, new EntityDiff() );
	}

	private function newSimpleFingerprint() {
		$fingerprint = new Fingerprint();

		$fingerprint->setLabel( 'en', 'foo' );
		$fingerprint->setDescription( 'de', 'bar' );
		$fingerprint->setAliasGroup( 'nl', [ 'baz' ] );

		return $fingerprint;
	}

	private function assertFingerprintResultsFromPatch(
		Fingerprint $expected,
		Fingerprint $original,
		EntityDiff $patch
	) {
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

		$patch = new EntityDiff( [
			'label' => new Diff( [
				'en' => new DiffOpChange( 'foo', 'bar' ),
				'de' => new DiffOpAdd( 'baz' ),
			], true ),
		] );

		$expectedFingerprint = $this->newSimpleFingerprint();
		$expectedFingerprint->setLabel( 'en', 'bar' );
		$expectedFingerprint->setLabel( 'de', 'baz' );

		$this->assertFingerprintResultsFromPatch( $expectedFingerprint, $fingerprint, $patch );
	}

	public function testDescriptionDiffOnlyAffectsDescriptions() {
		$fingerprint = $this->newSimpleFingerprint();

		$patch = new EntityDiff( [
			'description' => new Diff( [
				'de' => new DiffOpChange( 'bar', 'foo' ),
				'en' => new DiffOpAdd( 'baz' ),
			], true ),
		] );

		$expectedFingerprint = $this->newSimpleFingerprint();
		$expectedFingerprint->setDescription( 'de', 'foo' );
		$expectedFingerprint->setDescription( 'en', 'baz' );

		$this->assertFingerprintResultsFromPatch( $expectedFingerprint, $fingerprint, $patch );
	}

	public function aliasDiffProvider() {
		return [
			'diffs containing add/remove ops (default)' => [ [
				'de' => new Diff( [ new DiffOpAdd( 'foo' ) ], false ),
				'en' => new Diff( [ new DiffOpRemove( 'en-old' ), new DiffOpAdd( 'en-new' ) ], false ),
				'fa' => new Diff( [ new DiffOpRemove( 'fa-old' ) ], false ),
			] ],
			'associative diffs containing atomic ops' => [ [
				'de' => new Diff( [ new DiffOpAdd( 'foo' ) ], true ),
				'en' => new Diff( [ new DiffOpChange( 'en-old', 'en-new' ) ], true ),
				'fa' => new Diff( [ new DiffOpRemove( 'fa-old' ) ], true ),
			] ],
			'non-associative diffs containing atomic ops' => [ [
				'de' => new Diff( [ new DiffOpAdd( 'foo' ) ], true ),
				'en' => new Diff( [ new DiffOpChange( 'en-old', 'en-new' ) ], false ),
				'fa' => new Diff( [ new DiffOpRemove( 'fa-old' ) ], true ),
			] ],
			'partly associative diffs (auto-detected) containing atomic ops' => [ [
				'de' => new Diff( [ new DiffOpAdd( 'foo' ) ] ),
				'en' => new Diff( [ new DiffOpChange( 'en-old', 'en-new' ) ] ),
				'fa' => new Diff( [ new DiffOpRemove( 'fa-old' ) ] ),
			] ],
			'atomic diff ops' => [ [
				'de' => new DiffOpAdd( [ 'foo' ] ),
				'en' => new DiffOpChange( [ 'en-old' ], [ 'en-new' ] ),
				'fa' => new DiffOpRemove( [ 'fa-old' ] ),
			] ],
		];
	}

	/**
	 * @dataProvider aliasDiffProvider
	 */
	public function testAliasDiffOnlyAffectsAliases( array $diffOps ) {
		$fingerprint = $this->newSimpleFingerprint();
		$fingerprint->setAliasGroup( 'en', [ 'en-old' ] );
		$fingerprint->setAliasGroup( 'fa', [ 'fa-old' ] );

		$patch = new EntityDiff( [
			'aliases' => new Diff( $diffOps, true ),
		] );

		$expectedFingerprint = $this->newSimpleFingerprint();
		$expectedFingerprint->setAliasGroup( 'de', [ 'foo' ] );
		$expectedFingerprint->setAliasGroup( 'en', [ 'en-new' ] );

		$this->assertFingerprintResultsFromPatch( $expectedFingerprint, $fingerprint, $patch );
	}

	public function conflictingEditProvider() {
		return [
			'does not add existing label language' => [ [
				'label' => new Diff( [
					'en' => new DiffOpAdd( 'added' ),
				], true ),
			] ],
			'does not change modified label' => [ [
				'label' => new Diff( [
					'en' => new DiffOpChange( 'original', 'changed' ),
				], true ),
			] ],
			'does not change missing label' => [ [
				'label' => new Diff( [
					'de' => new DiffOpChange( 'original', 'changed' ),
				], true ),
			] ],
			'does not remove modified label' => [ [
				'label' => new Diff( [
					'en' => new DiffOpRemove( 'original' ),
				], true ),
			] ],
			'removing missing label is no-op' => [ [
				'label' => new Diff( [
					'de' => new DiffOpRemove( 'original' ),
				], true ),
			] ],

			'does not add existing description language' => [ [
				'description' => new Diff( [
					'en' => new DiffOpAdd( 'added' ),
				], true ),
			] ],
			'does not change modified description' => [ [
				'description' => new Diff( [
					'en' => new DiffOpChange( 'original', 'changed' ),
				], true ),
			] ],
			'changing missing description is no-op' => [ [
				'description' => new Diff( [
					'de' => new DiffOpChange( 'original', 'changed' ),
				], true ),
			] ],
			'does not remove modified description' => [ [
				'description' => new Diff( [
					'en' => new DiffOpRemove( 'original' ),
				], true ),
			] ],
			'removing missing description is no-op' => [ [
				'description' => new Diff( [
					'de' => new DiffOpRemove( 'original' ),
				], true ),
			] ],

			'does not add existing aliases language' => [ [
				'aliases' => new Diff( [
					'en' => new DiffOpAdd( [ 'added' ] ),
				], true ),
			] ],
			'does not change missing aliases language' => [ [
				'aliases' => new Diff( [
					'de' => new Diff( [ new DiffOpRemove( 'original' ), new DiffOpAdd( 'changed' ) ] ),
				], true ),
			] ],
			'changing missing aliases is no-op' => [ [
				'aliases' => new Diff( [
					'de' => new Diff( [ new DiffOpChange( 'original', 'changed' ) ], true ),
					'en' => new Diff( [ new DiffOpChange( 'original', 'changed' ) ], true ),
				], true ),
			] ],
			'changing missing aliases is no-op (atomic)' => [ [
				'aliases' => new Diff( [
					'de' => new DiffOpChange( [ 'original' ], [ 'changed' ] ),
					'en' => new DiffOpChange( [ 'original' ], [ 'changed' ] ),
				], true ),
			] ],
			'removing missing aliases is no-op' => [ [
				'aliases' => new Diff( [
					'de' => new Diff( [ new DiffOpRemove( 'original' ) ] ),
					'en' => new Diff( [ new DiffOpRemove( 'original' ) ] ),
				], true ),
			] ],
			'removing missing aliases is no-op (atomic)' => [ [
				'aliases' => new Diff( [
					'de' => new DiffOpRemove( [ 'original' ] ),
					'en' => new DiffOpRemove( [ 'original' ] ),
				], true ),
			] ],
		];
	}

	/**
	 * @dataProvider conflictingEditProvider
	 */
	public function testGivenConflictingEdit_fingerprintIsReturnedAsIs( array $diffOps ) {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'conflict' );
		$fingerprint->setDescription( 'en', 'conflict' );
		$fingerprint->setAliasGroup( 'en', [ 'conflict' ] );

		$patch = new EntityDiff( $diffOps );

		$this->assertFingerprintResultsFromPatch( $fingerprint, $fingerprint, $patch );
	}

}
