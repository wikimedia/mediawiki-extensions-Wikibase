<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Fields\SiteLinkCountField;

/**
 * @covers Wikibase\Repo\Search\Fields\SiteLinkCountField
 *
 * @group WikibaseRepo
 * @group WikibaseSearch
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCountFieldTest extends \PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$siteLinkCountField = new SiteLinkCountField();

		$expected = array(
			'type' => 'long'
		);

		$this->assertSame( $expected, $siteLinkCountField->getMapping() );
	}

	/**
	 * @dataProvider buildDataProvider
	 */
	public function testBuildData( $expected, $entity ) {
		$siteLinkCountField = new SiteLinkCountField();

		$this->assertSame( $expected, $siteLinkCountField->buildData( $entity ) );
	}

	public function buildDataProvider() {
		$item = new Item();

		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getSiteLinkList()->addNewSiteLink( 'eswiki', 'Gato' );

		return array(
			array( 2, $item ),
			array( 0, Property::newFromType( 'string' ) )
		);
	}

}
