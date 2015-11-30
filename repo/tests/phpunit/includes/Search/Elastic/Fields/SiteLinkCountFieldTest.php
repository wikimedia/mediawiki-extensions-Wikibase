<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Fields\SiteLinkCountField;

/**
 * @covers Wikibase\Repo\Elastic\Search\Fields\SiteLinkCountField
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCountFieldTest extends \PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$siteLinkCountField = new SiteLinkCountField();

		$expected = array(
			'type' => 'integer'
		);

		$this->assertSame( $expected, $siteLinkCountField->getMapping() );
	}

	/**
	 * @dataProvider getFieldDataProvider
	 */
	public function testGetFieldData( $expected, $entity ) {
		$siteLinkCountField = new SiteLinkCountField();

		$this->assertSame( $expected, $siteLinkCountField->getFieldData( $entity ) );
	}

	public function getFieldDataProvider() {
		$item = new Item();

		$item->getSiteLinkList()->addNewSiteLink( 'enwiki', 'Kitten' );
		$item->getSiteLinkList()->addNewSiteLink( 'eswiki', 'Gato' );

		return array(
			array( 2, $item ),
			array( 0, Property::newFromType( 'string' ) )
		);
	}

}
