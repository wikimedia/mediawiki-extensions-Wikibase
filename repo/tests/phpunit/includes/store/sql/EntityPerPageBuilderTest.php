<?php

namespace Wikibase\Test;

use FakeResultWrapper;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\EntityContentFactory;
use Wikibase\EntityPerPageBuilder;
use Wikibase\EntityPerPageBuilderPagesFinder;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Property;
use Wikibase\PropertyContent;
use Wikibase\Test\MockEntityPerPage;

/**
 * @covers Wikibase\EntityPerPageBuilder
 *
 * @todo cover all of the code paths and build options
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group WikibaseEntityPerPage
 * @group EntityPerPageBuilder
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityPerPageBuilderTest extends \PHPUnit_Framework_TestCase {

	public function testRebuild() {
		$entityPerPageBuilder = new EntityPerPageBuilder(
			$this->getEntityContentFactory(),
			new BasicEntityIdParser(),
			$this->getPagesFinder(),
			$this->getEntityNamespaces()
		);

		$entityPerPage = new MockEntityPerPage();
		$entityPerPage = $entityPerPageBuilder->rebuild( $entityPerPage, true );

		$entityIds = $entityPerPage->getEntities();

		$actualEntityIds = array();

		foreach( $entityIds as $entityId ) {
			$actualEntityIds[] = $entityId->getSerialization();
		}

		$expected = array( 'Q150', 'Q151', 'P97', 'P98', 'Q152' );

		$this->assertEquals( $expected, $actualEntityIds );
	}

	private function getEntityContentFactory() {
		$entityContentFactory = $this->getMockBuilder( 'Wikibase\EntityContentFactory' )
			->disableOriginalConstructor()
			->getMock();

		$entityContentFactory->expects( $this->any() )
			->method( 'getFromId' )
			->will( $this->returnCallback( function( $entityId ) {
					$contentModel = 'wikibase-' . $entityId->getEntityType();

					switch( $contentModel ) {
						case CONTENT_MODEL_WIKIBASE_ITEM:
							$item = Item::newEmpty();
							$item->setId( $entityId );
							return new ItemContent( $item );
						case CONTENT_MODEL_WIKIBASE_PROPERTY:
							$property = Property::newEmpty();
							$property->setId( $entityId );
							return new PropertyContent( $property );
						default;
							// @fixme support all entity types
							throw new InvalidArgumentException( 'unknown entity type' );
					}
				} )
			);

		$entityContentFactory->expects( $this->any() )
			->method( 'getEntityContentModels' )
			->will( $this->returnValue( array(
					Item::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_ITEM,
			 		Property::ENTITY_TYPE => CONTENT_MODEL_WIKIBASE_PROPERTY
			 	) )
			);

		return $entityContentFactory;
	}

	private function getPagesFinder() {
		$pagesFinder = $this->getMockBuilder( 'Wikibase\EntityPerPageBuilderPagesFinder' )
			->disableOriginalConstructor()
			->getMock();

		$pagesFinder->expects( $this->any() )
			->method( 'getPages' )
			->will( $this->returnCallback( function( $startAfter, $batchSize ) {
					$pages = array(
						array(
							'page_id' => 0,
							'page_title' => 'Q150',
							'page_namespace' => 0,
							'page_content_model' => null
						),
						array(
							'page_id' => 1,
							'page_title' => 'Q151',
							'page_namespace' => 0,
							'page_content_model' => null
						),
						array(
							'page_id' => 2,
							'page_title' => 'P97',
							'page_namespace' => 102,
							'page_content_model' => null
						),
						array(
							'page_id' => 3,
							'page_title' => 'P98',
							'page_namespace' => 102,
							'page_content_model' => null
						),
						array(
							'page_id' => 4,
							'page_title' => 'Q152',
							'page_namespace' => 0,
							'page_content_model' => null
						)
					);

					return new FakeResultWrapper( $pages );
				} )
			);

		return $pagesFinder;
	}

	private function getEntityNamespaces() {
		return array(
			'wikibase-item' => 0,
			'wikibase-property' => 102
		);
	}

}
