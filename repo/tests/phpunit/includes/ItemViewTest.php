<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @covers Wikibase\ItemView
 *
 * @group Wikibase
 * @group WikibaseItemView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @group Database
 * @group medium
 */
class ItemViewTest extends EntityViewTest {

	protected function getEntityViewClass() {
		return 'Wikibase\ItemView';
	}

	/**
	 * @param EntityId $id
	 * @param Statement[] $statements
	 *
	 * @return Entity
	 */
	protected function makeEntity( EntityId $id, array $statements = array() ) {
		return $this->makeItem( $id, $statements );
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return EntityId
	 */
	protected function makeEntityId( $n ) {
		return new ItemId( "Q$n");
	}

	public function getParserOutputLinksProvider() {
		$argLists = array();

		$p11 = new PropertyId( 'P11' );
		$p23 = new PropertyId( 'P42' );
		$p44 = new PropertyId( 'P44' );

		$q23 = new ItemId( 'Q23' );
		$q24 = new ItemId( 'Q24' );

		$argLists["PropertyNoValueSnak"] = array(
			array( $this->makeStatement( new PropertyNoValueSnak( $p44 ) ) ),
			array( $p44 ) );

		$argLists["PropertySomeValueSnak"] = array(
			array( $this->makeStatement( new PropertySomeValueSnak( $p44 ) ) ),
			array( $p44 ) );

		$argLists["PropertyValueSnak with string value"] = array(
			array( $this->makeStatement( new PropertyValueSnak( $p23, new StringValue( 'onoez' ) ) ) ),
			array( $p23 ) );

		$argLists["PropertyValueSnak with EntityId"] = array(
			array( $this->makeStatement( new PropertyValueSnak( $p44, new EntityIdValue( $q23 ) ) ) ),
			array( $p44, $q23 ) );

		$argLists["Mixed Snaks"] = array(
			array(
				$this->makeStatement( new PropertyValueSnak( $p11, new EntityIdValue( $q23 ) ) ),
				$this->makeStatement( new PropertyNoValueSnak( $p44 ) ),
				$this->makeStatement( new PropertySomeValueSnak( $p44 ) ),
				$this->makeStatement( new PropertyValueSnak( $p44, new StringValue( 'onoez' ) ) ),
				$this->makeStatement( new PropertyValueSnak( $p44, new EntityIdValue( $q24 ) ) ),
			),
			array( $p11, $q23, $p44, $q24 ) );

		return $argLists;
	}

	/**
	 * @dataProvider getParserOutputLinksProvider
	 *
	 * @param Statement[] $statements
	 * @param EntityId[] $expectedLinks
	 */
	public function testParserOutputLinks( array $statements, $expectedLinks ) {
		$entityRevision = $this->newEntityRevisionForStatements( $statements );
		$entityView = $this->newEntityView( $entityRevision->getEntity()->getType() );

		$out = $entityView->getParserOutput( $entityRevision, true, false );
		$links = $out->getLinks();

		// convert expected links to link structure
		foreach ( $expectedLinks as $entityId ) {
			$title = $this->getTitleForId( $entityId );
			$ns = $title->getNamespace();
			$dbk = $title->getDBkey();

			$this->assertArrayHasKey( $ns, $links, "sub-array for namespace" );
			$this->assertArrayHasKey( $dbk, $links[$ns], "entry for database key" );
		}
	}

	public function getParserOutputExternalLinksProvider() {
		$argLists = array();

		$p23 = new PropertyId( 'P23' );
		$p42 = new PropertyId( 'P42' );

		$argLists["empty"] = array(
			array(),
			array() );

		$argLists["PropertyNoValueSnak"] = array(
			array( $this->makeStatement( new PropertyNoValueSnak( $p42 ) ) ),
			array());

		$argLists["PropertySomeValueSnak"] = array(
			array( $this->makeStatement( new PropertySomeValueSnak( $p42 ) ) ),
			array() );

		$argLists["PropertyValueSnak with string value"] = array(
			array( $this->makeStatement( new PropertyValueSnak( $p23, new StringValue( 'http://not/a/url' )  ) ) ),
			array() );

		$argLists["PropertyValueSnak with URL"] = array(
			array( $this->makeStatement( new PropertyValueSnak( $p42, new StringValue( 'http://acme.com/test' ) ) ) ),
			array( 'http://acme.com/test' ) );

		return $argLists;
	}

	/**
	 * @dataProvider getParserOutputExternalLinksProvider
	 *
	 * @param Statement[] $statements
	 * @param string[] $expectedLinks
	 */
	public function testParserOutputExternalLinks( array $statements, $expectedLinks ) {
		$entityRevision = $this->newEntityRevisionForStatements( $statements );
		$entityView = $this->newEntityView( $entityRevision->getEntity()->getType() );

		$out = $entityView->getParserOutput( $entityRevision, true, false );
		$links = $out->getExternalLinks();

		$expectedLinks = array_values( $expectedLinks );
		sort( $expectedLinks );

		$links = array_keys( $links );
		sort( $links );

		$this->assertEquals( $expectedLinks, $links );
	}

	public function getParserOutputImageLinksProvider() {
		$argLists = array();

		$p23 = new PropertyId( 'P23' );
		$p43 = new PropertyId( 'P43' );

		$argLists["empty"] = array(
			array(),
			array() );

		$argLists["PropertyNoValueSnak"] = array(
			array( $this->makeStatement( new PropertyNoValueSnak( $p43 ) ) ),
			array() );

		$argLists["PropertySomeValueSnak"] = array(
			array( $this->makeStatement( new PropertySomeValueSnak( $p43 ) ) ),
			array() );

		$argLists["PropertyValueSnak with string value"] = array(
			array( $this->makeStatement( new PropertyValueSnak( $p23, new StringValue( 'not an image' )  ) ) ),
			array() );

		$argLists["PropertyValueSnak with image"] = array(
			array( $this->makeStatement( new PropertyValueSnak( $p43, new StringValue( 'File:Image.jpg' ) ) ) ),
			array( 'File:Image.jpg' ) );

		return $argLists;
	}

	/**
	 * @dataProvider getParserOutputImageLinksProvider
	 *
	 * @param Statement[] $statements
	 * @param string[] $expectedImages
	 */
	public function testGetParserOutputImageLinks( array $statements, array $expectedImages ) {
		$entityRevision = $this->newEntityRevisionForStatements( $statements );
		$entityView = $this->newEntityView( $entityRevision->getEntity()->getType() );

		$out = $entityView->getParserOutput( $entityRevision, true, false );
		$images = $out->getImages();

		$expectedImages = array_values( $expectedImages );
		sort( $expectedImages );

		$images = array_keys( $images );
		sort( $images );

		$this->assertEquals( $expectedImages, $images );
	}

}
