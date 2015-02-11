<?php

namespace Wikibase\Test;

use MessageCache;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\View\EntityTermsView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Repo\View\TextInjector;
use Wikibase\Template\TemplateFactory;
use Wikibase\Template\TemplateRegistry;

/**
 * @covers Wikibase\Repo\View\EntityTermsView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 */
class EntityTermsViewTest extends \MediaWikiLangTestCase {

	protected function setUp() {
		parent::setUp();

		$msgCache = MessageCache::singleton();
		$msgCache->enable();

		// Mocks for all "this is empty" placeholders
		$msgCache->replace( 'Wikibase-label-empty', '<strong class="test">No label</strong>' );
		$msgCache->replace( 'Wikibase-description-empty', '<strong class="test">No description</strong>' );
		$msgCache->replace( 'Wikibase-aliases-empty', '<strong class="test">No aliases</strong>' );
	}

	protected function tearDown() {
		$msgCache = MessageCache::singleton();
		$msgCache->disable();

		parent::tearDown();
	}

	private function getEntityTermsView( $languageCode = 'en' ) {
		$templateFactory = new TemplateFactory( TemplateRegistry::getDefaultInstance() );

		return new EntityTermsView(
			$templateFactory,
			new SectionEditLinkGenerator( $templateFactory ),
			$this->getMock( 'Wikibase\Lib\ContentLanguages' ),
			$languageCode
		);
	}

	private function getFingerprint( $languageCode = 'en' ) {
		$fingerprint = Fingerprint::newEmpty();
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
			'empty' => array( Fingerprint::newEmpty(), new ItemId( 'Q42' ), 'en' ),
			'other language' => array( $fingerprint, new ItemId( 'Q42' ), 'de' ),
			'other id' => array( $fingerprint, new ItemId( 'Q12' ), 'en' ),
		);
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetHtml_isEditable( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$entityTermsView = $this->getEntityTermsView( $languageCode );
		$html = $entityTermsView->getHtml( $fingerprint, $entityId, '', new TextInjector() );
		$idString = $entityId->getSerialization();

		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetLabel/' . $idString . '/' . $languageCode . '"@', $html );
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetHtml_isNotEditable( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$entityTermsView = $this->getEntityTermsView( $languageCode );
		$html = $entityTermsView->getHtml( $fingerprint, $entityId, '', new TextInjector(), false );

		$this->assertNotContains( '<a ', $html );
	}

	public function testGetHtml_valuesAreEscaped() {
		$entityTermsView = $this->getEntityTermsView();
		$fingerprint = Fingerprint::newEmpty();
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
			array( Fingerprint::newEmpty(), 'No' ),
			array( $noLabel, 'No label' ),
			array( $noDescription, 'No description' ),
			array( $noAliases, 'No aliases' ),
		);
	}

	/**
	 * @dataProvider emptyFingerprintProvider
	 */
	public function testGetHtml_isMarkedAsEmptyValue( Fingerprint $fingerprint ) {
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
		$entityTermsView = $this->getEntityTermsView( $languageCode );
		$html = $entityTermsView->getHtml( $fingerprint, $entityId, '', new TextInjector() );
		$idString = $entityId->getSerialization();

		$this->assertContains( '(' . $idString . ')', $html );
		$this->assertContains( '<a ', $html );
	}

	public function testGetHtml_withoutEntityId() {
		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getHtml( Fingerprint::newEmpty(), null, '', new TextInjector() );

		$this->assertNotContains( '(new)', $html );
		$this->assertNotContains( '<a ', $html );
	}

	/**
	 * @dataProvider emptyFingerprintProvider
	 */
	public function testGetHtml_containsIsEmptyPlaceholders( Fingerprint $fingerprint, $message ) {
		$entityTermsView = $this->getEntityTermsView();
		$html = $entityTermsView->getHtml( $fingerprint, null, '', new TextInjector() );

		$this->assertContains( $message, $html );
		$this->assertContains( 'strong', $html, 'make sure the setUp works' );
		$this->assertNotContains( '<strong class="test">', $html );
	}

}
