<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikibase\View\KeyNameLocalizedTextProvider;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TextInjector;

/**
 * @covers Wikibase\View\EntityTermsView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 * @uses Wikibase\View\TextInjector
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 */
class EntityTermsViewTest extends PHPUnit_Framework_TestCase {

	private function getEntityTermsView(
		$editSectionCalls = 0,
		$languageNameCalls = 0,
		$languageCode = 'en',
		LocalizedTextProvider $textProvider = null
	) {
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$editSectionGenerator->expects( $this->exactly( $editSectionCalls ) )
			->method( 'getLabelDescriptionAliasesEditSection' )
			->will( $this->returnValue( '<EDITSECTION>' ) );

		$languageNameLookup = $this->getMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->exactly( $languageNameCalls ) )
			->method( 'getName' )
			->will( $this->returnCallback( function( $languageCode ) {
				return "<LANGUAGENAME-$languageCode>";
			} ) );

		if ( $textProvider === null ) {
			$textProvider = new KeyNameLocalizedTextProvider( 'lkt' );
		}

		return new EntityTermsView(
			TemplateFactory::getDefaultInstance(),
			$editSectionGenerator,
			$languageNameLookup,
			$languageCode,
			$textProvider
		);
	}

	private function getFingerprint( $languageCode = 'en' ) {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( $languageCode, '<LABEL>' );
		$fingerprint->setDescription( $languageCode, '<DESCRIPTION>' );
		$fingerprint->setAliasGroup( $languageCode, array( '<ALIAS1>', '<ALIAS2>' ) );
		return $fingerprint;
	}

	public function testGetHtml_containsDescriptionAndAliases() {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$fingerprint = $this->getFingerprint();
		$html = $entityTermsView->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( '&lt;DESCRIPTION&gt;', $html );
		$this->assertContains( '&lt;ALIAS1&gt;', $html );
		$this->assertContains( '&lt;ALIAS2&gt;', $html );
	}

	public function entityFingerprintProvider() {
		$fingerprint = $this->getFingerprint();

		return array(
			'empty' => array( new Fingerprint(), new ItemId( 'Q42' ), 'en' ),
			'other language' => array( $fingerprint, new ItemId( 'Q42' ), 'de' ),
			'other id' => array( $fingerprint, new ItemId( 'Q12' ), 'en' ),
		);
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetHtml_isEditable( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$entityTermsView = $this->getEntityTermsView( 1, 0, $languageCode );
		$html = $entityTermsView->getHtml( $fingerprint, $entityId, '', new TextInjector() );

		$this->assertContains( '<EDITSECTION>', $html );
	}

	public function testGetHtml_valuesAreEscaped() {
		$fingerprint = new Fingerprint();
		$fingerprint->setDescription( 'en', '<script>alert( "xss" );</script>' );
		$fingerprint->setAliasGroup( 'en', array( '<a href="#">evil html</a>', '<b>bold</b>', '<i>italic</i>' ) );

		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( 'evil html', $html, 'make sure it works' );
		$this->assertNotContains( 'href="#"', $html );
		$this->assertNotContains( '<script>', $html );
		$this->assertNotContains( '<b>', $html );
		$this->assertNotContains( '<i>', $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

	public function testGetHtml_isMarkedAsEmptyValue() {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$html = $entityTermsView->getHtml( new Fingerprint(), null, '', new TextInjector() );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-description-empty)', $html );
		$this->assertContains( '(wikibase-aliases-empty)', $html );
	}

	public function testGetHtml_isNotMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$html = $entityTermsView->getHtml( $this->getFingerprint(), null, '', new TextInjector() );

		$this->assertNotContains( 'wb-empty', $html );
		$this->assertNotContains( '(wikibase-description-empty)', $html );
		$this->assertNotContains( '(wikibase-aliases-empty)', $html );
	}

	public function testGetHtml_containsEmptyDescriptionPlaceholder() {
		$fingerprint = $this->getFingerprint();
		$fingerprint->removeDescription( 'en' );

		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-description-empty)', $html );
		$this->assertNotContains( '(wikibase-aliases-empty)', $html );
	}

	public function testGetHtml_containsEmptyAliasesPlaceholder() {
		$fingerprint = $this->getFingerprint();
		$fingerprint->removeAliasGroup( 'en' );

		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( 'wb-empty', $html );
		$this->assertNotContains( '(wikibase-description-empty)', $html );
		$this->assertContains( '(wikibase-aliases-empty)', $html );
	}

	public function testGetTitleHtml_containsLabel() {
		$entityTermsView = $this->getEntityTermsView();
		$fingerprint = $this->getFingerprint();
		$html = $entityTermsView->getTitleHtml( $fingerprint, null );

		$this->assertContains( '&lt;LABEL&gt;', $html );
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetTitleHtml_withEntityId( Fingerprint $fingerprint, ItemId $entityId ) {
		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getTitleHtml( $fingerprint, $entityId );
		$idString = $entityId->getSerialization();

		$this->assertContains( '(parentheses: ' . $idString . ')', $html );
	}

	public function testGetTitleHtml_withoutEntityId() {
		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getTitleHtml( new Fingerprint(), null );

		$this->assertNotContains( '(parentheses', $html );
	}

	public function testGetTitleHtml_labelIsEscaped() {
		$entityTermsView = $this->getEntityTermsView();
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', '<a href="#">evil html</a>' );
		$html = $entityTermsView->getTitleHtml( $fingerprint, null );

		$this->assertContains( 'evil html', $html, 'make sure it works' );
		$this->assertNotContains( 'href="#"', $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

	public function testGetTitleHtml_isMarkedAsEmpty() {
		$fingerprint = $this->getFingerprint();
		$fingerprint->removeLabel( 'en' );

		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getTitleHtml( $fingerprint, null );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-label-empty)', $html );
	}

	public function testGetTitleHtml_isNotMarkedAsEmpty() {
		$fingerprint = $this->getFingerprint();

		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getTitleHtml( $fingerprint, null );

		$this->assertNotContains( 'wb-empty', $html );
		$this->assertNotContains( '(wikibase-label-empty)', $html );
	}

	public function testGetEntityTermsForLanguageListView() {
		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getLocalURL' )
			->will( $this->returnValue( '<LOCALURL>' ) );

		$item = new Item(
			new ItemId( 'Q1' ),
			$this->getFingerprint()
		);
		$view = $this->getEntityTermsView( 0, 1 );
		$html = $view->getEntityTermsForLanguageListView( $item, $item, $item, array( 'en' ), $title );

		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-language)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-label)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-description)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-aliases)', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertContains( '&lt;LOCALURL&gt;', $html );
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
		$view = $this->getEntityTermsView( 0, 1 );
		$html = $view->getEntityTermsForLanguageListView( $item, $item, $item, [ 'en' ] );

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
		$view = $this->getEntityTermsView( 0, 0, 'en', $textProvider );
		$html = $view->getEntityTermsForLanguageListView( $item, $item, $item, [] );

		$this->assertContains( '&quot;RAW&quot;', $html );
		$this->assertNotContains( '"RAW"', $html );
	}

	public function testGetEntityTermsForLanguageListView_isMarkedAsEmpty() {
		$item = new Item(
			new ItemId( 'Q1' ),
			new Fingerprint()
		);
		$view = $this->getEntityTermsView( 0, 1 );
		$html = $view->getEntityTermsForLanguageListView( $item, $item, $item, [ 'en' ] );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-label-empty)', $html );
		$this->assertContains( '(wikibase-description-empty)', $html );
		$this->assertNotContains( '(wikibase-aliases-empty)', $html );
	}

	public function testGetEntityTermsForLanguageListView_noAliasesProvider() {
		$title = $this->getMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getLocalURL' )
			->will( $this->returnValue( '<LOCALURL>' ) );

		$item = new Item(
			new ItemId( 'Q1' ),
			$this->getFingerprint()
		);
		$view = $this->getEntityTermsView( 0, 1 );
		$html = $view->getEntityTermsForLanguageListView( $item, $item, null, array( 'en' ), $title );

		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-language)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-label)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-description)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-aliases)', $html );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertContains( '&lt;LOCALURL&gt;', $html );
		$this->assertContains( '&lt;LANGUAGENAME-en&gt;', $html );
		$this->assertContains( '&lt;LABEL&gt;', $html );
		$this->assertContains( '&lt;DESCRIPTION&gt;', $html );
		$this->assertNotContains( '&lt;ALIAS1&gt;', $html );
		$this->assertNotContains( '&lt;ALIAS2&gt;', $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

}
