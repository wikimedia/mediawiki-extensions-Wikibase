<?php

namespace Wikibase\Test;

use Language;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityViewPlaceholderExpander;
use Wikibase\Item;

/**
 * @covers Wikibase\EntityViewPlaceholderExpander
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpanderTest extends \MediaWikiTestCase {

	protected function newExpander( $user ) {
		$title = new Title( 'EntityViewPlaceholderExpanderTest-DummyTitleForLocalUrls' );

		$language = Language::factory( 'en' );

		$entity = Item::newEmpty();
		$entity->setId( new ItemId( 'Q23' ) );

		$entity->setLabel( 'en', 'Moskow' );
		$entity->setLabel( 'de', 'Moskau' );

		$entity->setDescription( 'de', 'Hauptstadt Russlands' );

		$idParser = $this->getMockBuilder( 'Wikibase\DataModel\Entity\EntityIdParser' )
			->disableOriginalConstructor()
			->getMock();

		$idParser->expects( $this->any() )
			->method( 'parse' )
			->will( $this->returnValue( $entity->getId() ) );

		$entityLookup = $this->getMock( 'Wikibase\Lib\Store\EntityLookup' );
		$idParser->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnValue( $entity ) );

		$userLanguages = $this->getMockBuilder( 'Wikibase\UserLanguageLookup' )
			->disableOriginalConstructor()
			->getMock();

		$userLanguages->expects( $this->any() )
			->method( 'getExtraUserLanguages' )
			->will( $this->returnValue( array( 'de', 'en', 'ru' ) ) );

		return new EntityViewPlaceholderExpander(
			$title,
			$user,
			$language,
			$idParser,
			$entityLookup,
			$userLanguages
		);
	}

	private function newUser( $isAnon ) {
		$user = $this->getMockBuilder( '\User' )
			->disableOriginalConstructor()
			->getMock();
		$user->expects( $this->any() )
			->method( 'isAnon' )
			->will( $this->returnValue( $isAnon ) );
		$user->setName( 'EntityViewPlaceholderExpanderTest-DummyUser' );

		return $user;
	}

	public function testGetHtmlForPlaceholder() {
		$expander = $this->newExpander( $this->newUser( false ) );

		$html = $expander->getHtmlForPlaceholder( 'termbox-toc' );
		$this->assertInternalType( 'string', $html );

		$html = $expander->getHtmlForPlaceholder( 'termbox', 'Q23' );
		$this->assertInternalType( 'string', $html );
	}

	public function testRenderTermBoxTocEntry() {
		$expander = $this->newExpander( $this->newUser( false ) );

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->renderTermBoxTocEntry( new ItemId( 'Q23' ) );
		$this->assertNotNull( $html );
		$this->assertRegExp( '/#wb-terms/', $html );
	}

	public function renderTermBox() {
		$expander = $this->newExpander( $this->newUser( false ) );

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->renderTermBox( new ItemId( 'Q23' ) );

		$this->assertRegExp( '/Moskau/', $html );
		$this->assertRegExp( '/Hauptstadt/', $html );

		$this->assertNotRegExp( '/Moskow/', $html );
		$this->assertNotRegExp( '/Capitol/', $html );
	}

	public function testGetExtraUserLanguages() {
		$expander = $this->newExpander( $this->newUser( true ) );
		$this->assertArrayEquals( array(), $expander->getExtraUserLanguages() );

		$expander = $this->newExpander( $this->newUser( false ) );
		$this->assertArrayEquals( array( 'de', 'en', 'ru' ), $expander->getExtraUserLanguages() );
	}

}
