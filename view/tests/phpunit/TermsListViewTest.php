<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\TermsListView;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\TermsListView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class TermsListViewTest extends PHPUnit_Framework_TestCase {

	private function getTermsListView(
		$languageNameCalls = 0,
		LocalizedTextProvider $textProvider = null
	) {

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->exactly( $languageNameCalls ) )
			->method( 'getName' )
			->will( $this->returnCallback( function( $languageCode ) {
				return "<LANGUAGENAME-$languageCode>";
			} ) );

		$textProvider = $textProvider ?: new DummyLocalizedTextProvider( 'lkt' );

		return new TermsListView(
			TemplateFactory::getDefaultInstance(),
			$languageNameLookup,
			$textProvider,
			$this->getMock( LanguageDirectionalityLookup::class )
		);
	}

	private function getFingerprint( $languageCode = 'en' ) {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( $languageCode, '<LABEL>' );
		$fingerprint->setDescription( $languageCode, '<DESCRIPTION>' );
		$fingerprint->setAliasGroup( $languageCode, array( '<ALIAS1>', '<ALIAS2>' ) );
		return $fingerprint;
	}

	public function testGetEntityTermsForLanguageListView() {
		$item = new Item(
			new ItemId( 'Q1' ),
			$this->getFingerprint()
		);
		$view = $this->getTermsListView( 1 );
		$html = $view->getHtml( $item, $item, $item, array( 'en' ) );

		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-language)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-label)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-description)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-aliases)', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertContains( '&lt;LANGUAGENAME-en&gt;', $html );
		$this->assertContains( '&lt;LABEL&gt;', $html );
		$this->assertContains( '&lt;DESCRIPTION&gt;', $html );
		$this->assertContains( '&lt;ALIAS1&gt;', $html );
		$this->assertContains( '&lt;ALIAS2&gt;', $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

	public function testGetEntityTermsForLanguageListView_newEntity() {
		$item = new Item(
			null,
			new Fingerprint()
		);
		$view = $this->getTermsListView( 1 );
		$html = $view->getHtml( $item, $item, $item, [ 'en' ] );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-label-empty)', $html );
		$this->assertContains( '(wikibase-description-empty)', $html );
		$this->assertNotContains( '(wikibase-aliases-empty)', $html );
	}

	public function testGetEntityTermsForLanguageListView_isEscaped() {
		$textProvider = $this->getMock( LocalizedTextProvider::class );
		$textProvider->expects( $this->any() )
			->method( 'get' )
			->will( $this->returnCallback( function( $key ) {
				return $key === 'wikibase-entitytermsforlanguagelistview-language' ? '"RAW"' : "($key)";
			} ) );

		$item = new Item(
			new ItemId( 'Q1' ),
			new Fingerprint()
		);
		$view = $this->getTermsListView( 0, $textProvider );
		$html = $view->getHtml( $item, $item, $item, [] );

		$this->assertContains( '&quot;RAW&quot;', $html );
		$this->assertNotContains( '"RAW"', $html );
	}

	public function testGetEntityTermsForLanguageListView_isMarkedAsEmpty() {
		$item = new Item(
			new ItemId( 'Q1' ),
			new Fingerprint()
		);
		$view = $this->getTermsListView( 1 );
		$html = $view->getHtml( $item, $item, $item, [ 'en' ] );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-label-empty)', $html );
		$this->assertContains( '(wikibase-description-empty)', $html );
		$this->assertNotContains( '(wikibase-aliases-empty)', $html );
	}

	public function testGetEntityTermsForLanguageListView_noAliasesProvider() {
		$item = new Item(
			new ItemId( 'Q1' ),
			$this->getFingerprint()
		);
		$view = $this->getTermsListView( 1 );
		$html = $view->getHtml( $item, $item, null, array( 'en' ) );

		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-language)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-label)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-description)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-aliases)', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertContains( '&lt;LANGUAGENAME-en&gt;', $html );
		$this->assertContains( '&lt;LABEL&gt;', $html );
		$this->assertContains( '&lt;DESCRIPTION&gt;', $html );
		$this->assertNotContains( '&lt;ALIAS1&gt;', $html );
		$this->assertNotContains( '&lt;ALIAS2&gt;', $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

}
