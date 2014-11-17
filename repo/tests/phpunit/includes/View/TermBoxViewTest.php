<?php

namespace Wikibase\Test;

use Language;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\View\TermBoxView;

/**
 * @covers Wikibase\Repo\View\TermBoxView
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class TermBoxViewTest extends \PHPUnit_Framework_TestCase {

	public function testRenderTermBox() {
		$language = Language::factory( 'qqx' ); // so we can look for message keys in the output
		$view = new TermBoxView( $language );

		$title = Title::newFromText( 'TermBoxViewTest-DummyTitle' );

		$entity = Item::newEmpty();
		$entity->setId( new ItemId( 'Q23' ) );

		$fingerprint = $entity->getFingerprint();
		$fingerprint->setLabel( 'en', 'Moskow' );
		$fingerprint->setLabel( 'de', 'Moskau' );

		$fingerprint->setDescription( 'de', 'Hauptstadt Russlands' );

		$entity->setFingerprint( $fingerprint );

		$languages = array( 'de', 'ru' );

		$html = $view->renderTermBox( $title, $entity->getFingerprint(), $languages );

		$this->assertNotRegExp( '/Moskow/', $html, 'unexpected English label, should not be there' );

		$this->assertRegExp( '/Moskau/', $html, 'expected German label' );
		$this->assertRegExp( '/Hauptstadt/', $html, 'expected German description' );

		$this->assertRegExp( '/wikibase-label-empty/', $html, 'expected label-empty message for "ru"' );
		$this->assertRegExp( '!<h2 id="wb-terms".*?>\(wikibase-terms\)</h2>!', $html, 'expected h2 header' );
	}

}
