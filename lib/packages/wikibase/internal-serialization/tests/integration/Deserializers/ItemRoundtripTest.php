<?php

namespace Tests\Integration\Wikibase\InternalSerialization\Deserializers;

use Deserializers\Deserializer;
use Tests\Integration\Wikibase\InternalSerialization\TestFactoryBuilder;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @covers Wikibase\InternalSerialization\Deserializers\ItemDeserializer
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemRoundtripTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Deserializer
	 */
	private $deserializer;

	protected function setUp() {
		$this->deserializer = TestFactoryBuilder::newLegacyDeserializerFactory( $this )->newEntityDeserializer();
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testSerializationRoundtripping( Item $item ) {
		$newItem = $this->deserializer->deserialize( $item->toArray() );

		$this->assertTrue( $item->equals( $newItem ) );
	}

	public function itemProvider() {
		return array(
			array( $this->newSimpleItem() ),

			array( $this->newItemWithSiteLinks() ),
			array( $this->newItemWithFingerprint() ),
			array( $this->newItemWithClaims() ),

			array( $this->newComplexItem() ),
		);
	}

	private function newSimpleItem() {
		return Item::newEmpty();
	}

	private function newItemWithSiteLinks() {
		$item = $this->newSimpleItem();

		$this->addSiteLinks( $item );

		return $item;
	}

	private function addSiteLinks( Item $item ) {
		$item->addSiteLink( new SiteLink( 'foo', 'bar' ) );
		$item->addSiteLink( new SiteLink( 'baz', 'bah' ) );
	}

	private function newItemWithFingerprint() {
		$item = $this->newSimpleItem();

		$this->addFingerprint( $item );

		return $item;
	}

	private function addFingerprint( Item $item ) {
		$fingerprint = $item->getFingerprint();

		$fingerprint->getLabels()->setTerm( new Term( 'en', 'foo' ) );
		$fingerprint->getLabels()->setTerm( new Term( 'de', 'bar' ) );

		$fingerprint->getDescriptions()->setTerm( new Term( 'en', 'foo bar baz' ) );
		$fingerprint->getDescriptions()->setTerm( new Term( 'nl', 'blah' ) );

		$fingerprint->getAliases()->setGroup( new AliasGroup( 'en', array( 'foo', 'bar', 'baz' ) ) );
		$fingerprint->getAliases()->setGroup( new AliasGroup( 'fr', array( 'spam' ) ) );
	}

	private function newItemWithClaims() {
		$item = $this->newSimpleItem();

		$this->addClaims( $item );

		return $item;
	}

	private function addClaims( Item $item ) {
		$claim1 = new Claim( new PropertyNoValueSnak( 1 ) );
		$claim2 = new Claim( new PropertyNoValueSnak( 2 ) );
		$claim3 = new Claim( new PropertyNoValueSnak( 3 ) );

		$claim1->setGuid( 'claim 1' );
		$claim2->setGuid( 'claim 2' );
		$claim3->setGuid( 'claim 3' );

		$item->addClaim( $claim1 );
		$item->addClaim( $claim2 );
		$item->addClaim( $claim3 );
	}

	private function newComplexItem() {
		$item = $this->newSimpleItem();

		$this->addSiteLinks( $item );
		$this->addFingerprint( $item );
		$this->addClaims( $item );

		return $item;
	}

}