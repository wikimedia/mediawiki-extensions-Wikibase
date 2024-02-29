<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\ParserOutput\PlaceholderExpander;

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\LanguageFallbackChainFactory;
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

	private function newExpander( UserIdentity $user, Item $item ): EntityViewPlaceholderExpander {
		$templateFactory = TemplateFactory::getDefaultInstance();

		$termsLanguages = [ 'de', 'en', 'ru' ];

		$languageNameLookup = $this->createMock( LanguageNameLookup::class );

		return new EntityViewPlaceholderExpander(
			$templateFactory,
			$user,
			$item,
			$termsLanguages,
			$this->createMock( LanguageDirectionalityLookup::class ),
			$languageNameLookup,
			new DummyLocalizedTextProvider(),
			MediaWikiServices::getInstance()->getUserOptionsLookup(),
			$this->createMock( LanguageFallbackChainFactory::class ),
			false
		);
	}

	private function newItem(): Item {
		$item = new Item( new ItemId( 'Q23' ) );
		$item->setLabel( 'en', 'Moskow' );
		$item->setLabel( 'de', 'Moskau' );
		$item->setDescription( 'de', 'Hauptstadt Russlands' );

		return $item;
	}

	private function newUser( bool $isAnon = false ): UserIdentity {
		$user = $this->createMock( UserIdentity::class );
		$user->method( 'isRegistered' )
			->willReturn( !$isAnon );
		return $user;
	}

	public function testGetHtmlForPlaceholderTermbox_resultIsHtmlString(): void {
		$expander = $this->newExpander( $this->newUser(), $this->newItem() );

		$html = $expander->getHtmlForPlaceholder( 'termbox' );
		$this->assertIsString( $html );
	}

	public function testGetHtmlForPlaceholderTermbox_resultContainsLabelsAndDescriptionsInAllLanguages(): void {
		$expander = $this->newExpander( $this->newUser(), $this->newItem() );

		// According to the mock objects, this should generate a term box for
		// 'de' and 'ru', since 'en' is already covered by the interface language.
		$html = $expander->getHtmlForPlaceholder( 'termbox' );

		$this->assertStringContainsString( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertStringContainsString( 'Moskow', $html );

		$this->assertStringContainsString( 'wikibase-entitytermsforlanguageview-de', $html );
		$this->assertStringContainsString( 'Moskau', $html );
		$this->assertStringContainsString( 'Hauptstadt Russlands', $html );

		$this->assertStringContainsString( 'wikibase-entitytermsforlanguageview-ru', $html );
	}

	public function testGivenNoCookie_placeholderIsInitiallyExpanded(): void {
		$expander = $this->newExpander( $this->newUser( true ), $this->newItem() );

		$html = $expander->getHtmlForPlaceholder( 'entityViewPlaceholder-entitytermsview-entitytermsforlanguagelistview-class' );

		$this->assertSame( '', $html );
	}

}
