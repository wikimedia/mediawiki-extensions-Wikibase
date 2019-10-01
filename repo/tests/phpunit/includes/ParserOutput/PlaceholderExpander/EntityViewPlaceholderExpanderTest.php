<?php

namespace Wikibase\Repo\Tests\ParserOutput\PlaceholderExpander;

use PHPUnit4And6Compat;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Repo\ParserOutput\PlaceholderExpander\EntityViewPlaceholderExpander;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers \Wikibase\Repo\ParserOutput\PlaceholderExpander\EntityViewPlaceholderExpander
 *
 * @uses \Wikibase\View\TermsListView
 * @uses \Wikibase\View\Template\Template
 * @uses \Wikibase\View\Template\TemplateFactory
 * @uses \Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EntityViewPlaceholderExpanderTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	/* private */ const COOKIE_PREFIX = 'testwiki-';

	/**
	 * @param User $user
	 * @param Item $item
	 * @param string $cookiePrefix
	 *
	 * @return EntityViewPlaceholderExpander
	 */
	private function newExpander( User $user, Item $item, $cookiePrefix = '' ) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		$termsLanguages = [ 'de', 'en', 'ru' ];

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );

		return new EntityViewPlaceholderExpander(
			$templateFactory,
			$user,
			$item,
			$termsLanguages,
			$this->getMock( LanguageDirectionalityLookup::class ),
			$languageNameLookup,
			new DummyLocalizedTextProvider(),
			$cookiePrefix
		);
	}

	private function newItem() {
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setLabel( 'en', 'Moskow' );
		$item->setLabel( 'de', 'Moskau' );
		$item->setDescription( 'de', 'Hauptstadt Russlands' );

		return $item;
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

	public function testGetHtmlForPlaceholderTermbox_resultIsHtmlString() {
		$expander = $this->newExpander( $this->newUser(), $this->newItem() );

		$html = $expander->getHtmlForPlaceholder( 'termbox' );
		$this->assertInternalType( 'string', $html );
	}

	public function testGetHtmlForPlaceholderTermbox_resultContainsLabelsAndDescriptionsInAllLanguages() {
		$expander = $this->newExpander( $this->newUser(), $this->newItem() );

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->getHtmlForPlaceholder( 'termbox' );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertContains( 'Moskow', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-de', $html );
		$this->assertContains( 'Moskau', $html );
		$this->assertContains( 'Hauptstadt Russlands', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-ru', $html );
	}

	public function testGivenCookieSetToTrue_placeholderIsInitiallyExpanded() {
		$expander = $this->newExpander( $this->newUser( true ), $this->newItem(), self::COOKIE_PREFIX );

		$cookieName = self::COOKIE_PREFIX . EntityViewPlaceholderExpander::INITIALLY_COLLAPSED_SETTING_NAME;
		$_COOKIE[$cookieName] = 'true';

		$html = $expander->getHtmlForPlaceholder( 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' );

		$this->assertSame( '', $html );

		unset( $_COOKIE[$cookieName] ); // avoid side effects
	}

	public function testGivenCookieSetToFalse_placeholderIsInitiallyCollapsed() {
		$expander = $this->newExpander( $this->newUser( true ), $this->newItem(), self::COOKIE_PREFIX );

		$cookieName = self::COOKIE_PREFIX . EntityViewPlaceholderExpander::INITIALLY_COLLAPSED_SETTING_NAME;
		$_COOKIE[$cookieName] = 'false';

		$html = $expander->getHtmlForPlaceholder( 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' );

		$this->assertEquals( 'wikibase-initially-collapsed', $html );

		unset( $_COOKIE[$cookieName] ); // avoid side effects
	}

	public function testGivenNoCookie_placeholderIsInitiallyExpanded() {
		$expander = $this->newExpander( $this->newUser( true ), $this->newItem(), self::COOKIE_PREFIX );

		unset( $_COOKIE[self::COOKIE_PREFIX . EntityViewPlaceholderExpander::INITIALLY_COLLAPSED_SETTING_NAME] );

		$html = $expander->getHtmlForPlaceholder( 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' );

		$this->assertSame( '', $html );
	}

}
