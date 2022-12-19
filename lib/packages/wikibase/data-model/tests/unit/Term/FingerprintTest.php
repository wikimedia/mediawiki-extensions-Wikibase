<?php

namespace Wikibase\DataModel\Tests\Term;

use OutOfBoundsException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers \Wikibase\DataModel\Term\Fingerprint
 * @uses \Wikibase\DataModel\Term\AliasGroup
 * @uses \Wikibase\DataModel\Term\AliasGroupList
 * @uses \Wikibase\DataModel\Term\Fingerprint
 * @uses \Wikibase\DataModel\Term\Term
 * @uses \Wikibase\DataModel\Term\TermList
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class FingerprintTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @var TermList
	 */
	private $labels;

	/**
	 * @var TermList
	 */
	private $descriptions;

	/**
	 * @var AliasGroupList
	 */
	private $aliasGroups;

	/**
	 * @var Fingerprint
	 */
	private $fingerprint;

	protected function setUp(): void {
		$this->labels = $this->createMock( TermList::class );
		$this->descriptions = $this->createMock( TermList::class );
		$this->aliasGroups = $this->createMock( AliasGroupList::class );

		$this->fingerprint = new Fingerprint(
			new TermList( [
				new Term( 'en', 'enlabel' ),
				new Term( 'de', 'delabel' ),
			] ),
			new TermList( [
				new Term( 'en', 'endescription' ),
				new Term( 'de', 'dedescription' ),
			] ),
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'enalias' ] ),
				new AliasGroup( 'de', [ 'dealias' ] ),
			] )
		);
	}

	public function testEmptyConstructor() {
		$fingerprint = new Fingerprint();

		$this->assertTrue( $fingerprint->getLabels()->isEmpty() );
		$this->assertTrue( $fingerprint->getDescriptions()->isEmpty() );
		$this->assertTrue( $fingerprint->getAliasGroups()->isEmpty() );
	}

	public function testConstructorSetsValues() {
		$fingerprint = new Fingerprint( $this->labels, $this->descriptions, $this->aliasGroups );

		$this->assertEquals( $this->labels, $fingerprint->getLabels() );
		$this->assertEquals( $this->descriptions, $fingerprint->getDescriptions() );
		$this->assertEquals( $this->aliasGroups, $fingerprint->getAliasGroups() );
	}

	public function testGetLabel() {
		$term = new Term( 'en', 'enlabel' );
		$this->assertEquals( $term, $this->fingerprint->getLabel( 'en' ) );
	}

	public function testSetLabel() {
		$term = new Term( 'en', 'changed' );
		$this->fingerprint->setLabel( 'en', 'changed' );
		$this->assertEquals( $term, $this->fingerprint->getLabel( 'en' ) );
	}

	public function testRemoveLabel() {
		$labels = new TermList( [
			new Term( 'de', 'delabel' ),
		] );
		$this->fingerprint->removeLabel( 'en' );
		$this->assertEquals( $labels, $this->fingerprint->getLabels() );
	}

	public function testRemoveLabelMakesGetterThrowException() {
		$this->expectException( OutOfBoundsException::class );
		$this->fingerprint->removeLabel( 'en' );
		$this->fingerprint->getLabel( 'en' );
	}

	public function testGetDescription() {
		$term = new Term( 'en', 'endescription' );
		$this->assertEquals( $term, $this->fingerprint->getDescription( 'en' ) );
	}

	public function testSetDescription() {
		$description = new Term( 'en', 'changed' );
		$this->fingerprint->setDescription( 'en', 'changed' );
		$this->assertEquals( $description, $this->fingerprint->getDescription( 'en' ) );
	}

	public function testRemoveDescription() {
		$descriptions = new TermList( [
			new Term( 'de', 'dedescription' ),
		] );
		$this->fingerprint->removeDescription( 'en' );
		$this->assertEquals( $descriptions, $this->fingerprint->getDescriptions() );
	}

	public function testRemoveDescriptionMakesGetterThrowException() {
		$this->expectException( OutOfBoundsException::class );
		$this->fingerprint->removeDescription( 'en' );
		$this->fingerprint->getDescription( 'en' );
	}

	public function testGetAliasGroup() {
		$aliasGroup = new AliasGroup( 'en', [ 'enalias' ] );
		$this->assertEquals( $aliasGroup, $this->fingerprint->getAliasGroup( 'en' ) );
	}

	public function testSetAliasGroup() {
		$aliasGroup = new AliasGroup( 'en', [ 'changed' ] );
		$this->fingerprint->setAliasGroup( 'en', [ 'changed' ] );
		$this->assertEquals( $aliasGroup, $this->fingerprint->getAliasGroup( 'en' ) );
	}

	public function testRemoveAliasGroup() {
		$aliasGroups = new AliasGroupList( [
			new AliasGroup( 'de', [ 'dealias' ] ),
		] );
		$this->fingerprint->removeAliasGroup( 'en' );
		$this->assertEquals( $aliasGroups, $this->fingerprint->getAliasGroups() );
	}

	public function testRemoveAliasGroupMakesGetterThrowException() {
		$this->expectException( OutOfBoundsException::class );
		$this->fingerprint->removeAliasGroup( 'en' );
		$this->fingerprint->getAliasGroup( 'en' );
	}

	/**
	 * @dataProvider fingerprintProvider
	 */
	public function testFingerprintsEqualThemselves( Fingerprint $fingerprint ) {
		$this->assertTrue( $fingerprint->equals( $fingerprint ) );
		$this->assertTrue( $fingerprint->equals( clone $fingerprint ) );
	}

	public function fingerprintProvider() {
		return [
			[
				new Fingerprint(),
			],
			[
				new Fingerprint(
					new TermList( [ new Term( 'en', 'foo' ) ] )
				),
			],
			[
				new Fingerprint(
					new TermList(),
					new TermList( [ new Term( 'en', 'foo' ) ] )
				),
			],
			[
				new Fingerprint(
					new TermList(),
					new TermList(),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] )
				),
			],
			[
				new Fingerprint(
					new TermList( [ new Term( 'nl', 'bar' ), new Term( 'fr', 'le' ) ] ),
					new TermList( [ new Term( 'de', 'baz' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] )
				),
			],
		];
	}

	/**
	 * @dataProvider differentFingerprintsProvider
	 */
	public function testDifferentFingerprintsDoNotEqual( Fingerprint $one, Fingerprint $two ) {
		$this->assertFalse( $one->equals( $two ) );
	}

	public function differentFingerprintsProvider() {
		return [
			[
				new Fingerprint(),
				new Fingerprint(
					new TermList( [ new Term( 'en', 'foo' ) ] )
				),
			],
			[
				new Fingerprint(
					new TermList( [ new Term( 'en', 'foo' ), new Term( 'de', 'bar' ) ] )
				),
				new Fingerprint(
					new TermList( [ new Term( 'en', 'foo' ) ] )
				),
			],
			[
				new Fingerprint(),
				new Fingerprint(
					new TermList(),
					new TermList( [ new Term( 'en', 'foo' ) ] )
				),
			],
			[
				new Fingerprint(),
				new Fingerprint(
					new TermList(),
					new TermList(),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] )
				),
			],
			[
				new Fingerprint(
					new TermList( [ new Term( 'nl', 'bar' ), new Term( 'fr', 'le' ) ] ),
					new TermList( [ new Term( 'de', 'HAX' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] )
				),
				new Fingerprint(
					new TermList( [ new Term( 'nl', 'bar' ), new Term( 'fr', 'le' ) ] ),
					new TermList( [ new Term( 'de', 'baz' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] )
				),
			],
		];
	}

	public function testEmptyFingerprintIsEmpty() {
		$fingerprint = new Fingerprint();
		$this->assertTrue( $fingerprint->isEmpty() );
	}

	/**
	 * @dataProvider nonEmptyFingerprintProvider
	 */
	public function testNonEmptyFingerprintIsNotEmpty( Fingerprint $nonEmptyFingerprint ) {
		$this->assertFalse( $nonEmptyFingerprint->isEmpty() );
	}

	public function nonEmptyFingerprintProvider() {
		return [
			[
				new Fingerprint(
					new TermList( [ new Term( 'en', 'foo' ) ] )
				),
			],

			[
				new Fingerprint(
					new TermList(),
					new TermList( [ new Term( 'en', 'foo' ) ] )
				),
			],

			[
				new Fingerprint(
					new TermList(),
					new TermList(),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] )
				),
			],

			[
				new Fingerprint(
					new TermList( [ new Term( 'nl', 'bar' ), new Term( 'fr', 'le' ) ] ),
					new TermList( [ new Term( 'de', 'baz' ) ] ),
					new AliasGroupList( [ new AliasGroup( 'en', [ 'foo' ] ) ] )
				),
			],
		];
	}

	public function testSetLabels() {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'foo' );

		$labels = new TermList( [
			new Term( 'de', 'bar' ),
		] );

		$fingerprint->setLabels( $labels );

		$this->assertEquals( $labels, $fingerprint->getLabels() );
	}

	public function testSetDescriptions() {
		$fingerprint = new Fingerprint();
		$fingerprint->setDescription( 'en', 'foo' );

		$descriptions = new TermList( [
			new Term( 'de', 'bar' ),
		] );

		$fingerprint->setDescriptions( $descriptions );

		$this->assertEquals( $descriptions, $fingerprint->getDescriptions() );
	}

	public function testSetAliasGroups() {
		$fingerprint = new Fingerprint();
		$fingerprint->setAliasGroup( 'en', [ 'foo' ] );

		$groups = new AliasGroupList( [
			new AliasGroup( 'de', [ 'bar' ] ),
		] );

		$fingerprint->setAliasGroups( $groups );

		$this->assertEquals( $groups, $fingerprint->getAliasGroups() );
	}

	public function testEmptyFingerprintDoesNotHaveLabel() {
		$fingerprint = new Fingerprint();
		$this->assertFalse( $fingerprint->hasLabel( 'en' ) );
	}

	public function testEmptyFingerprintDoesNotHaveDescription() {
		$fingerprint = new Fingerprint();
		$this->assertFalse( $fingerprint->hasDescription( 'en' ) );
	}

	public function testEmptyFingerprintDoesNotHaveAliasGroup() {
		$fingerprint = new Fingerprint();
		$this->assertFalse( $fingerprint->hasAliasGroup( 'en' ) );
	}

	public function testHasLabelReturnsTrueOnlyWhenLabelExists() {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', 'foo' );

		$this->assertTrue( $fingerprint->hasLabel( 'en' ) );
		$this->assertFalse( $fingerprint->hasLabel( 'de' ) );
	}

	public function testHasDescriptionReturnsTrueOnlyWhenDescriptionExists() {
		$fingerprint = new Fingerprint();
		$fingerprint->setDescription( 'en', 'foo' );

		$this->assertTrue( $fingerprint->hasDescription( 'en' ) );
		$this->assertFalse( $fingerprint->hasDescription( 'de' ) );
	}

	public function testHasAliasGroupReturnsTrueOnlyWhenAliasGroupExists() {
		$fingerprint = new Fingerprint();
		$fingerprint->setAliasGroup( 'en', [ 'foo' ] );

		$this->assertTrue( $fingerprint->hasAliasGroup( 'en' ) );
		$this->assertFalse( $fingerprint->hasAliasGroup( 'de' ) );
	}

}
