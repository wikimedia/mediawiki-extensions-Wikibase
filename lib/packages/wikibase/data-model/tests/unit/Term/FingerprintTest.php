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

	/**
	 * @expectedException OutOfBoundsException
	 */
	public function testRemoveAliasGroupMakesGetterThrowException() {
		$this->fingerprint->removeAliasGroup( 'en' );
		$this->fingerprint->getAliasGroup( 'en' );
	}

}
