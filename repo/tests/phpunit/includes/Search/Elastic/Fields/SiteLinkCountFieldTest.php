<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use DummySearchIndexFieldDefinition;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\Search\Elastic\Fields\SiteLinkCountField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\SiteLinkCountField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class SiteLinkCountFieldTest extends PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$siteLinkCountField = new SiteLinkCountField();

		$searchEngine = $this->getMockBuilder( 'SearchEngine' )->getMock();

		$searchEngine->expects( $this->any() )
			->method( 'makeSearchFieldMapping' )
			->will( $this->returnCallback( function ( $name, $type ) {
				return new DummySearchIndexFieldDefinition( $name, $type );
			} ) );

		$mapping =
			$siteLinkCountField->getMapping( $searchEngine, "sitelink_field" )
				->getMapping( $searchEngine );
		$this->assertEquals( \SearchIndexField::INDEX_TYPE_INTEGER, $mapping['type'] );
		$this->assertEquals( "sitelink_field", $mapping['name'] );
	}

	/**
	 * @dataProvider getFieldDataProvider
	 */
	public function testGetFieldData( $expected, EntityDocument $entity ) {
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
