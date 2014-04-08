<?php

namespace Wikibase\DataModel\Term\Test;

use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Label;
use Wikibase\DataModel\Term\LabelList;

/**
 * @covers Wikibase\DataModel\Term\Fingerprint
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class FingerprintTest extends \PHPUnit_Framework_TestCase {

	private $labels;
	private $descriptions;
	private $aliases;

	public function setUp() {
		$this->labels = $this->getMockBuilder( 'Wikibase\DataModel\Term\LabelList' )
			->disableOriginalConstructor()->getMock();

		$this->descriptions = $this->getMockBuilder( 'Wikibase\DataModel\Term\DescriptionList' )
			->disableOriginalConstructor()->getMock();

		$this->aliases = $this->getMockBuilder( 'Wikibase\DataModel\Term\AliasGroupList' )
			->disableOriginalConstructor()->getMock();
	}

	public function testConstructorSetsValues() {
		$fingerprint = new Fingerprint( $this->labels, $this->descriptions, $this->aliases );

		$this->assertEquals( $this->labels, $fingerprint->getLabels() );
		$this->assertEquals( $this->descriptions, $fingerprint->getDescriptions() );
		$this->assertEquals( $this->aliases, $fingerprint->getAliases() );
	}

	public function testGivenLabelForNewLanguage_setLabelAddsLabel() {
		$labels = new LabelList( array(
			new Label( 'en', 'foo' ),
			new Label( 'de', 'bar' ),
		) );

		$fingerprint = new Fingerprint( $labels, $this->descriptions, $this->aliases );
		$label = new Label( 'nl', 'spam' );

		$fingerprint->setLabel( $label );
		$this->assertEquals( $label, $fingerprint->getLabels()->getByLanguage( 'nl' ) );
		$this->assertCount( 3, $fingerprint->getLabels() );
	}

	public function testGivenLabelForExistingLanguage_setLabelReplacesLabel() {
		$labels = new LabelList( array(
			new Label( 'en', 'foo' ),
			new Label( 'de', 'bar' ),
		) );

		$fingerprint = new Fingerprint( $labels, $this->descriptions, $this->aliases );
		$label = new Label( 'en', 'spam' );

		$fingerprint->setLabel( $label );
		$this->assertEquals( $label, $fingerprint->getLabels()->getByLanguage( 'en' ) );
		$this->assertCount( 2, $fingerprint->getLabels() );
	}

}
