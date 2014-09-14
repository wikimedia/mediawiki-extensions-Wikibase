<?php

namespace Wikibase\Test;

use Language;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\EntityRevision;
use Wikibase\Repo\View\EntityView;

/**
 * @covers Wikibase\Repo\View\EntityView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityViewTest extends \PHPUnit_Framework_TestCase {

	public function testGetHtml_newItem() {
		$entityView = $this->getEntityView( 'en' );
		$item = Item::newEmpty();
		$html = $entityView->getHtml( new EntityRevision( $item ) );

		$this->assertRegExp( '/<div[^>]*>INNER_HTML<\/div>/', $html );
		$this->assertRegExp( '/<div[^>]* id="wb-item-new"/', $html );
		$this->assertRegExp( '/<div[^>]* class="wikibase-entityview wb-item"/', $html );
		$this->assertRegExp( '/<div[^>]* dir="ltr"/', $html );
	}

	public function testGetHtml_existingItem() {
		$entityView = $this->getEntityView( 'en' );
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q42' ) );
		$html = $entityView->getHtml( new EntityRevision( $item ) );

		$this->assertRegExp( '/<div[^>]*>INNER_HTML<\/div>/', $html );
		$this->assertRegExp( '/<div[^>]* id="wb-item-Q42"/', $html );
		$this->assertRegExp( '/<div[^>]* class="wikibase-entityview wb-item"/', $html );
		$this->assertRegExp( '/<div[^>]* dir="ltr"/', $html );
	}

	public function testGetHtml_existingProperty() {
		$entityView = $this->getEntityView( 'en' );
		$property = Property::newEmpty();
		$property->setId( new PropertyId( 'P42' ) );
		$html = $entityView->getHtml( new EntityRevision( $property ) );

		$this->assertRegExp( '/<div[^>]*>INNER_HTML<\/div>/', $html );
		$this->assertRegExp( '/<div[^>]* id="wb-property-P42"/', $html );
		$this->assertRegExp( '/<div[^>]* class="wikibase-entityview wb-property"/', $html );
		$this->assertRegExp( '/<div[^>]* dir="ltr"/', $html );
	}

	public function testGetHtml_ltrLanguage() {
		$entityView = $this->getEntityView( 'fa' );
		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q42' ) );
		$html = $entityView->getHtml( new EntityRevision( $item ) );

		$this->assertRegExp( '/<div[^>]*>INNER_HTML<\/div>/', $html );
		$this->assertRegExp( '/<div[^>]* id="wb-item-Q42"/', $html );
		$this->assertRegExp( '/<div[^>]* class="wikibase-entityview wb-item"/', $html );
		$this->assertRegExp( '/<div[^>]* dir="rtl"/', $html );
	}

	/**
	 * @param string $langCode
	 * @return EntityView
	 */
	private function getEntityView( $langCode ) {
		$entityView = $this->getMockForAbstractClass( 'Wikibase\Repo\View\EntityView', array( Language::factory( $langCode ) ) );

		$entityView->expects( $this->any() )
			->method( 'getInnerHtml' )
			->will( $this->returnValue( 'INNER_HTML' ) );

		return $entityView;
	}

}
