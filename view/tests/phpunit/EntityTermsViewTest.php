<?php

namespace Wikibase\Test;

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

	private function getEntityTermsView( $languageCode = 'en', $called = null ) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		if ( $called === null ) {
			$called = $this->any();
		}

		$editSectionGenerator = $this->getMock( 'Wikibase\View\EditSectionGenerator' );

		$editSectionGenerator->expects( $called )
			->method( 'getLabelDescriptionAliasesEditSection' )
			->will( $this->returnValue( '~EDITSECTION~' ) );

		return new EntityTermsView(
			$templateFactory,
			$editSectionGenerator,
			$this->getMock( 'Wikibase\Lib\LanguageNameLookup' ),
			$languageCode
		);
	}

	private function getFingerprint( $languageCode = 'en' ) {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( $languageCode, 'Example label' );
		$fingerprint->setDescription( $languageCode, 'This is an example description' );
		$fingerprint->setAliasGroup(
			$languageCode,
			array(
				'sample alias',
				'specimen alias',
			)
		);
		return $fingerprint;
	}

	public function testGetHtml_containsTermsAndAliases() {
		$entityTermsView = $this->getEntityTermsView();
		$fingerprint = $this->getFingerprint();
		$html = $entityTermsView->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( htmlspecialchars( $fingerprint->getLabel( 'en' )->getText() ), $html );
		$this->assertContains( htmlspecialchars( $fingerprint->getDescription( 'en' )->getText() ), $html );
		foreach ( $fingerprint->getAliasGroup( 'en' )->getAliases() as $alias ) {
			$this->assertContains( htmlspecialchars( $alias ), $html );
		}
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
		$entityTermsView = $this->getEntityTermsView( $languageCode, $this->once() );
		$html = $entityTermsView->getHtml( $fingerprint, $entityId, '', new TextInjector() );

		$this->assertContains( '~EDITSECTION~', $html );
	}

	public function testGetHtml_valuesAreEscaped() {
		$entityTermsView = $this->getEntityTermsView();
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( 'en', '<a href="#">evil html</a>' );
		$fingerprint->setDescription( 'en', '<script>alert( "xss" );</script>' );
		$fingerprint->setAliasGroup( 'en', array( '<b>bold</b>', '<i>italic</i>' ) );
		$html = $entityTermsView->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( 'evil html', $html, 'make sure it works' );
		$this->assertNotContains( 'href="#"', $html );
		$this->assertNotContains( '<script>', $html );
		$this->assertNotContains( '<b>', $html );
		$this->assertNotContains( '<i>', $html );
	}

	public function emptyFingerprintProvider() {
		$noLabel = $this->getFingerprint();
		$noLabel->removeLabel( 'en' );

		$noDescription = $this->getFingerprint();
		$noDescription->removeDescription( 'en' );

		$noAliases = $this->getFingerprint();
		$noAliases->removeAliasGroup( 'en' );

		return array(
			array( new Fingerprint(), '-empty)' ),
			array( $noLabel, '(wikibase-label-empty)' ),
			array( $noDescription, '(wikibase-description-empty)' ),
			array( $noAliases, '(wikibase-aliases-empty)' ),
		);
	}

	/**
	 * @dataProvider emptyFingerprintProvider
	 */
	public function testGetHtml_isMarkedAsEmptyValue( Fingerprint $fingerprint, $expectedPlaceholder ) {
		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( 'wb-empty', $html );
	}

	public function testGetHtml_isNotMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getHtml( $this->getFingerprint(), null, '', new TextInjector() );

		$this->assertNotContains( 'wb-empty', $html );
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetHtml_withEntityId( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$entityTermsView = $this->getEntityTermsView( $languageCode, $this->once() );
		$html = $entityTermsView->getHtml( $fingerprint, $entityId, '', new TextInjector() );
		$idString = $entityId->getSerialization();

		$this->assertContains( '(parentheses: ' . $idString . ')', $html );
		$this->assertContains( '~EDITSECTION~', $html );
	}

	public function testGetHtml_withoutEntityId() {
		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getHtml( new Fingerprint(), null, '', new TextInjector() );

		$this->assertNotContains( '(parentheses', $html );
		$this->assertNotContains( '<a ', $html );
	}

	/**
	 * @dataProvider emptyFingerprintProvider
	 */
	public function testGetHtml_containsIsEmptyPlaceholders( Fingerprint $fingerprint, $expectedPlaceholder ) {
		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( $expectedPlaceholder, $html );
		$numberOfPlaceholders = $fingerprint->isEmpty() ? 3 : 1;
		$this->assertSame( $numberOfPlaceholders, substr_count( $html, $expectedPlaceholder ) );
	}

}
