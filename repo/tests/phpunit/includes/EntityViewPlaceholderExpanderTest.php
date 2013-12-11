<?php

namespace Wikibase\Test;
use Language;
use Title;
use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EntityViewPlaceholderExpander;
use Wikibase\Item;


/**
 * @covers Wikibase\EntityViewPlaceholderExpander
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpanderTest extends \MediaWikiTestCase {

	protected function newExpander() {
		$title = new Title( 'EntityViewPlaceholderExpanderTest-DummyTitleForLocalUrls' );

		$user = new User();
		$user->setName( 'EntityViewPlaceholderExpanderTest-DummyUser' );

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

		$entityLookup = $this->getMock( 'Wikibase\EntityLookup' );
		$idParser->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnValue( $entity ) );

		$userLanguages = $this->getMockBuilder( 'Wikibase\UserLanguageLookup' )
			->disableOriginalConstructor()
			->getMock();

		$userLanguages->expects( $this->any() )
			->method( 'getUserLanguages' )
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

	public function testGetHtmlForPlaceholder() {
		$expander = $this->newExpander();

		$html = $expander->getHtmlForPlaceholder( 'termbox-toc' );
		$this->assertInternalType( 'string', $html );

		$html = $expander->getHtmlForPlaceholder( 'termbox', 'Q23' );
		$this->assertInternalType( 'string', $html );
	}

	public function testRenderTermBoxTocEntry() {
		$expander = $this->newExpander();

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->renderTermBoxTocEntry( new ItemId( 'Q23' ) );

		$this->assertRegExp( '/#wb-terms/', $html );
	}

	public function renderTermBox() {
		$expander = $this->newExpander();

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->renderTermBox( new ItemId( 'Q23' ) );

		$this->assertRegExp( '/Moskau/', $html );
		$this->assertRegExp( '/Hauptstadt/', $html );

		$this->assertNotRegExp( '/Moskow/', $html );
		$this->assertNotRegExp( '/Capitol/', $html );
	}

}