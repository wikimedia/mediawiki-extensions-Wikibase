<?php

namespace Wikibase\Test\Entity\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Wikibase\DataModel\Entity\Diff\EntityDiff;
use Wikibase\DataModel\Entity\Diff\FingerprintPatcher;
use Wikibase\DataModel\Term\Fingerprint;

/**
 * @covers Wikibase\DataModel\Entity\Diff\FingerprintPatcher
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FingerprintPatcherTest extends \PHPUnit_Framework_TestCase {

	public function testGivenEmptyDiff_fingerprintIsReturnedAsIs() {
		$fingerprint = $this->newSimpleFingerprint();

		$this->assertFingerprintResultsFromPatch( $fingerprint, $fingerprint, new EntityDiff() );
	}

	private function newSimpleFingerprint() {
		$fingerprint = Fingerprint::newEmpty();

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

	public function testAliasDiffOnlyAffectsAliases() {
		$fingerprint = $this->newSimpleFingerprint();

		$patch = new EntityDiff( array(
			'aliases' => new Diff( array(
				'de' => new Diff( array( new DiffOpAdd( 'foo' ) ), true ),
			), true )
		) );

		$expectedFingerprint = $this->newSimpleFingerprint();
		$expectedFingerprint->setAliasGroup( 'de', array( 'foo' ) );

		$this->assertFingerprintResultsFromPatch( $expectedFingerprint, $fingerprint, $patch );
	}

}

