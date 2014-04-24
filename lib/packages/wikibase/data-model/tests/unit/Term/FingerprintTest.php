<?php

namespace Wikibase\DataModel\Term\Test;

use OutOfBoundsException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\DataModel\Term\Fingerprint
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Mättig
 */
class FingerprintTest extends \PHPUnit_Framework_TestCase {

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

	public function setUp() {
		$this->labels = $this->getMockBuilder( 'Wikibase\DataModel\Term\TermList' )
			->disableOriginalConstructor()->getMock();

		$this->descriptions = $this->getMockBuilder( 'Wikibase\DataModel\Term\TermList' )
			->disableOriginalConstructor()->getMock();

		$this->aliasGroups = $this->getMockBuilder( 'Wikibase\DataModel\Term\AliasGroupList' )
			->disableOriginalConstructor()->getMock();

		$this->fingerprint = new Fingerprint(
			new TermList( array(
				new Term( 'en', 'enlabel' ),
				new Term( 'de', 'delabel' ),
			) ),
			new TermList( array(
				new Term( 'en', 'endescription' ),
				new Term( 'de', 'dedescription' ),
			) ),
			new AliasGroupList( array(
				new AliasGroup( 'en', array( 'enalias' ) ),
				new AliasGroup( 'de', array( 'dealias' ) ),
			) )
		);
	}

	public function testConstructorSetsValues() {
		$fingerprint = new Fingerprint( $this->labels, $this->descriptions, $this->aliasGroups );

		$this->assertEquals( $this->labels, $fingerprint->getLabels() );
		$this->assertEquals( $this->descriptions, $fingerprint->getDescriptions() );
		$this->assertEquals( $this->aliasGroups, $fingerprint->getAliases() );
	}

	public function testGetLabel() {
		$term = new Term( 'en', 'enlabel' );
		$this->assertEquals( $term, $this->fingerprint->getLabel( 'en' ) );
	}

	public function testSetLabel() {
		$term = new Term( 'en', 'changed' );
		$this->fingerprint->setLabel( $term );
		$this->assertEquals( $term, $this->fingerprint->getLabel( 'en' ) );
	}

	public function testRemoveLabel() {
		$labels = new TermList( array(
			new Term( 'de', 'delabel' ),
		) );
		$this->fingerprint->removeLabel( 'en' );
		$this->assertEquals( $labels, $this->fingerprint->getLabels() );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testRemoveLabelMakesGetterThrowException() {
		$this->fingerprint->removeLabel( 'en' );
		$this->fingerprint->getLabel( 'en' );
	}

	public function testGetDescription() {
		$term = new Term( 'en', 'endescription' );
		$this->assertEquals( $term, $this->fingerprint->getDescription( 'en' ) );
	}

	public function testSetDescription() {
		$description = new Term( 'en', 'changed' );
		$this->fingerprint->setDescription( $description );
		$this->assertEquals( $description, $this->fingerprint->getDescription( 'en' ) );
	}

	public function testRemoveDescription() {
		$descriptions = new TermList( array(
			new Term( 'de', 'dedescription' ),
		) );
		$this->fingerprint->removeDescription( 'en' );
		$this->assertEquals( $descriptions, $this->fingerprint->getDescriptions() );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testRemoveDescriptionMakesGetterThrowException() {
		$this->fingerprint->removeDescription( 'en' );
		$this->fingerprint->getDescription( 'en' );
	}

	public function testGetAliasGroup() {
		$aliasGroup = new AliasGroup( 'en', array( 'enalias' ) );
		$this->assertEquals( $aliasGroup, $this->fingerprint->getAliasGroup( 'en' ) );
	}

	public function testSetAliasGroup() {
		$aliasGroup = new AliasGroup( 'en', array( 'changed' ) );
		$this->fingerprint->setAliasGroup( $aliasGroup );
		$this->assertEquals( $aliasGroup, $this->fingerprint->getAliasGroup( 'en' ) );
	}

	public function testRemoveAliasGroup() {
		$aliasGroups = new AliasGroupList( array(
			new AliasGroup( 'de', array( 'dealias' ) ),
		) );
		$this->fingerprint->removeAliasGroup( 'en' );
		$this->assertEquals( $aliasGroups, $this->fingerprint->getAliases() );
	}

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testRemoveAliasGroupMakesGetterThrowException() {
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
		return array(
			array(
				Fingerprint::newEmpty()
			),
			array(
				new Fingerprint(
					new TermList( array( new Term( 'en', 'foo' ) ) ),
					new TermList( array() ),
					new AliasGroupList( array() )
				)
			),
			array(
				new Fingerprint(
					new TermList( array() ),
					new TermList( array( new Term( 'en', 'foo' ) ) ),
					new AliasGroupList( array() )
				)
			),
			array(
				new Fingerprint(
					new TermList( array() ),
					new TermList( array() ),
					new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) )
				)
			),
			array(
				new Fingerprint(
					new TermList( array( new Term( 'nl', 'bar' ), new Term( 'fr', 'le' ) ) ),
					new TermList( array( new Term( 'de', 'baz' ) ) ),
					new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) )
				)
			),
		);
	}

	/**
	 * @dataProvider differentFingerprintsProvider
	 */
	public function testDifferentFingerprintsDoNotEqual( Fingerprint $one, Fingerprint $two ) {
		$this->assertFalse( $one->equals( $two ) );
	}

	public function differentFingerprintsProvider() {
		return array(
			array(
				Fingerprint::newEmpty(),
				new Fingerprint(
					new TermList( array( new Term( 'en', 'foo' ) ) ),
					new TermList( array() ),
					new AliasGroupList( array() )
				)
			),
			array(
				new Fingerprint(
					new TermList( array( new Term( 'en', 'foo' ), new Term( 'de', 'bar' ) ) ),
					new TermList( array() ),
					new AliasGroupList( array() )
				),
				new Fingerprint(
					new TermList( array( new Term( 'en', 'foo' ) ) ),
					new TermList( array() ),
					new AliasGroupList( array() )
				)
			),
			array(
				Fingerprint::newEmpty(),
				new Fingerprint(
					new TermList( array() ),
					new TermList( array( new Term( 'en', 'foo' ) ) ),
					new AliasGroupList( array() )
				)
			),
			array(
				Fingerprint::newEmpty(),
				new Fingerprint(
					new TermList( array() ),
					new TermList( array() ),
					new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) )
				)
			),
			array(
				new Fingerprint(
					new TermList( array( new Term( 'nl', 'bar' ), new Term( 'fr', 'le' ) ) ),
					new TermList( array( new Term( 'de', 'HAX' ) ) ),
					new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) )
				),
				new Fingerprint(
					new TermList( array( new Term( 'nl', 'bar' ), new Term( 'fr', 'le' ) ) ),
					new TermList( array( new Term( 'de', 'baz' ) ) ),
					new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) )
				)
			),
		);
	}

	public function testEmptyFingerprintIsEmpty() {
		$this->assertTrue( Fingerprint::newEmpty()->isEmpty() );
	}

	/**
	 * @dataProvider nonEmptyFingerprintProvider
	 */
	public function testNonEmptyFingerprintIsNotEmpty( Fingerprint $nonEmptyFingerprint ) {
		$this->assertFalse( $nonEmptyFingerprint->isEmpty() );
	}

	public function nonEmptyFingerprintProvider() {
		return array(
			array(
				new Fingerprint(
					new TermList( array( new Term( 'en', 'foo' ) ) ),
					new TermList( array() ),
					new AliasGroupList( array() )
				)
			),

			array(
				new Fingerprint(
					new TermList( array() ),
					new TermList( array( new Term( 'en', 'foo' ) ) ),
					new AliasGroupList( array() )
				)
			),

			array(
				new Fingerprint(
					new TermList( array() ),
					new TermList( array() ),
					new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) )
				)
			),

			array(
				new Fingerprint(
					new TermList( array( new Term( 'nl', 'bar' ), new Term( 'fr', 'le' ) ) ),
					new TermList( array( new Term( 'de', 'baz' ) ) ),
					new AliasGroupList( array( new AliasGroup( 'en', array( 'foo' ) ) ) )
				)
			),
		);
	}

	public function testSetLabels() {
		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setLabel( new Term( 'en', 'foo' ) );

		$labels = new TermList( array(
			new Term( 'de', 'bar' )
		) );

		$fingerprint->setLabels( $labels );

		$this->assertEquals( $labels, $fingerprint->getLabels() );
	}

	public function testSetDescriptions() {
		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setDescription( new Term( 'en', 'foo' ) );

		$descriptions = new TermList( array(
			new Term( 'de', 'bar' )
		) );

		$fingerprint->setDescriptions( $descriptions );

		$this->assertEquals( $descriptions, $fingerprint->getDescriptions() );
	}

	public function testSetAliasGroups() {
		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setAliasGroup( new AliasGroup( 'en', array( 'foo' ) ) );

		$groups = new AliasGroupList( array(
			new AliasGroup( 'de', array( 'bar' ) )
		) );

		$fingerprint->setAliasGroups( $groups );

		$this->assertEquals( $groups, $fingerprint->getAliasGroups() );
	}

}
