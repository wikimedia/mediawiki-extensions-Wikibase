<?php

namespace Wikibase\View\Tests;

use Language;
use MediaWikiTestCase;
use Title;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Lib\UserLanguageLookup;
use Wikibase\View\EntityViewPlaceholderExpander;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\EntityViewPlaceholderExpander
 *
 * @uses Wikibase\View\EntityTermsView
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpanderTest extends MediaWikiTestCase {

	/**
	 * @param User $user
	 * @param Item $item
	 * @param ItemId $itemId
	 * @param AliasesProvider|null $aliasesProvider
	 *
	 * @return EntityViewPlaceholderExpander
	 */
	private function newExpander( User $user, Item $item, ItemId $itemId, AliasesProvider $aliasesProvider = null ) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$language = Language::factory( 'en' );

		$userLanguages = $this->getMock( UserLanguageLookup::class );
		$userLanguages->expects( $this->any() )
			->method( 'getAllUserLanguages' )
			->will( $this->returnValue( array( 'de', 'en', 'ru' ) ) );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );

		return new EntityViewPlaceholderExpander(
			$templateFactory,
			$title,
			$user,
			$language,
			$item,
			$item,
			$aliasesProvider,
			$userLanguages,
			new MediaWikiContentLanguages(),
			$languageNameLookup
		);
	}

	public function provideEntityAndAliases() {
		$item = new Item( new ItemId( 'Q23' ) );

		$item->setLabel( 'en', 'Moskow' );
		$item->setLabel( 'de', 'Moskau' );

		$item->setDescription( 'de', 'Hauptstadt Russlands' );

		return [
			[
				$item,
				$item
			],
			[
				$item,
				null
			]
		];
	}

	/**
	 * @param bool $isAnon
	 *
	 * @return User
	 */
	private function newUser( $isAnon = false ) {
		$user = $this->getMockBuilder( User::class )
			->disableOriginalConstructor()
			->getMock();
		$user->expects( $this->any() )
			->method( 'isAnon' )
			->will( $this->returnValue( $isAnon ) );

		/** @var User $user */
		$user->setName( 'EntityViewPlaceholderExpanderTest-DummyUser' );

		return $user;
	}

	/**
	 * @dataProvider provideEntityAndAliases
	 */
	public function testGetHtmlForPlaceholder( Item $item, AliasesProvider $aliasesProvider = null ) {
		$expander = $this->newExpander( $this->newUser(), $item, $item->getId(), $aliasesProvider );

		$html = $expander->getHtmlForPlaceholder( 'termbox', 'Q23' );
		$this->assertInternalType( 'string', $html );
	}

	/**
	 * @dataProvider provideEntityAndAliases
	 */
	public function testRenderTermBox( Item $item, AliasesProvider $aliasesProvider = null ) {
		$expander = $this->newExpander( $this->newUser(), $item, $item->getId(), $aliasesProvider );

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->renderTermBox();

		$this->assertContains( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertContains( 'Moskow', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-de', $html );
		$this->assertContains( 'Moskau', $html );
		$this->assertContains( 'Hauptstadt Russlands', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-ru', $html );
	}

	/**
	 * @dataProvider provideEntityAndAliases
	 */
	public function testGetExtraUserLanguage( Item $item, AliasesProvider $aliasesProvider = null ) {
		$itemId = $item->getId();

		$expander = $this->newExpander( $this->newUser( true ), $item, $itemId, $aliasesProvider );
		$this->assertArrayEquals( [], $expander->getExtraUserLanguages() );

		$expander = $this->newExpander( $this->newUser(), $item, $itemId, $aliasesProvider );
		$this->assertArrayEquals( array( 'de', 'ru' ), $expander->getExtraUserLanguages() );
	}

}
