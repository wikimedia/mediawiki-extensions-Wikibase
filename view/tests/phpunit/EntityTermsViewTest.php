<?php

namespace Wikibase\View\Tests;

use Language;
use MediaWikiLangTestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\View\EntityTermsView;
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
 * @group Database
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 */
class EntityTermsViewTest extends MediaWikiLangTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgLang' => Language::factory( 'qqx' ),
		) );
	}

	private function getEntityTermsView(
		$editSectionCalls = 0,
		$languageNameCalls = 0,
		$languageCode = 'en'
	) {
		$editSectionGenerator = $this->getMock( 'Wikibase\View\EditSectionGenerator' );
		$editSectionGenerator->expects( $this->exactly( $editSectionCalls ) )
			->method( 'getLabelDescriptionAliasesEditSection' )
			->will( $this->returnValue( '<EDITSECTION>' ) );

		$languageNameLookup = $this->getMock( 'Wikibase\Lib\LanguageNameLookup' );
		$languageNameLookup->expects( $this->exactly( $languageNameCalls ) )
			->method( 'getName' )
			->will( $this->returnValue( '<LANGUAGENAME>' ) );

		return new EntityTermsView(
			TemplateFactory::getDefaultInstance(),
			$editSectionGenerator,
			$languageNameLookup,
			$languageCode
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
	}

	public function emptyFingerprintProvider() {
		$noDescription = $this->getFingerprint();
		$noDescription->removeDescription( 'en' );

		$noAliases = $this->getFingerprint();
		$noAliases->removeAliasGroup( 'en' );

		return array(
			array( new Fingerprint(), '-empty)' ),
			array( $noDescription, '(wikibase-description-empty)' ),
			array( $noAliases, '(wikibase-aliases-empty)' ),
		);
	}

	/**
	 * @dataProvider emptyFingerprintProvider
	 */
	public function testGetHtml_isMarkedAsEmptyValue( Fingerprint $fingerprint, $expectedPlaceholder ) {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$html = $entityTermsView->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( 'wb-empty', $html );
	}

	public function testGetHtml_isNotMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$html = $entityTermsView->getHtml( $this->getFingerprint(), null, '', new TextInjector() );

		$this->assertNotContains( 'wb-empty', $html );
	}

	/**
	 * @dataProvider emptyFingerprintProvider
	 */
	public function testGetHtml_containsIsEmptyPlaceholders( Fingerprint $fingerprint, $expectedPlaceholder ) {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$html = $entityTermsView->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( $expectedPlaceholder, $html );
		$numberOfPlaceholders = $fingerprint->isEmpty() ? 2 : 1;
		$this->assertSame( $numberOfPlaceholders, substr_count( $html, $expectedPlaceholder ) );
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
	}

	public function testGetTitleHtml_isMarkedAsEmpty() {
		$fingerprint = $this->getFingerprint();
		$fingerprint->removeLabel( 'en' );

		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getTitleHtml( $fingerprint, null );

		$this->assertContains( 'wb-empty', $html );
	}

	public function testGetTitleHtml_isNotMarkedAsEmpty() {
		$fingerprint = $this->getFingerprint();

		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getTitleHtml( $fingerprint, null );

		$this->assertNotContains( 'wb-empty', $html );
	}

	public function testGetEntityTermsForLanguageListView() {
		$title = $this->getMock( 'Title' );
		$title->expects( $this->once() )
			->method( 'getLocalURL' )
			->will( $this->returnValue( '<LOCALURL>' ) );

		$fingerprint = $this->getFingerprint();
		$view = $this->getEntityTermsView( 0, 1 );
		$html = $view->getEntityTermsForLanguageListView( $fingerprint, array( 'en' ), $title );

		$this->assertContains( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertContains( '&lt;LOCALURL&gt;', $html );
		$this->assertContains( '&lt;LANGUAGENAME&gt;', $html );
		$this->assertContains( '&lt;LABEL&gt;', $html );
		$this->assertContains( '&lt;DESCRIPTION&gt;', $html );
		$this->assertContains( '&lt;ALIAS1&gt;', $html );
		$this->assertContains( '&lt;ALIAS2&gt;', $html );
	}

}
