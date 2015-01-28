<?php

namespace Wikibase\Test;

use MessageCache;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;
use Wikibase\Template\TemplateFactory;

/**
 * @covers Wikibase\Repo\View\FingerprintView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Mättig
 */
class FingerprintViewTest extends \MediaWikiLangTestCase {

	protected function setUp() {
		parent::setUp();

		$msgCache = MessageCache::singleton();
		$msgCache->enable();

		// Mocks for all "this is empty" placeholders
		$msgCache->replace( 'Wikibase-label-empty', '<strong class="test">No label</strong>' );
		$msgCache->replace( 'Wikibase-description-empty', '<strong class="test">No description</strong>' );
		$msgCache->replace( 'Wikibase-aliases-empty', '<strong class="test">No aliases</strong>' );

		// Mock for the only other message in the class
		$msgCache->replace( 'Wikibase-aliases-label', '<strong class="test">A.&thinsp;k.&thinsp;a.:</strong>' );
	}

	protected function tearDown() {
		$msgCache = MessageCache::singleton();
		$msgCache->disable();

		parent::tearDown();
	}

	private function getFingerprintView( $languageCode = 'en' ) {
		$templateFactory = TemplateFactory::getDefaultInstance();

		return new FingerprintView(
			$templateFactory,
			new SectionEditLinkGenerator( $templateFactory ),
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
		$fingerprintView = $this->getFingerprintView();
		$fingerprint = $this->getFingerprint();
		$html = $fingerprintView->getHtml( $fingerprint );

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
		$fingerprintView = $this->getFingerprintView( $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId );
		$idString = $entityId->getSerialization();

		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetLabel/' . $idString . '/' . $languageCode . '"@', $html );
		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetDescription/' . $idString . '/' . $languageCode . '"@', $html );
		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetAliases/' . $idString . '/' . $languageCode . '"@', $html );
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetHtml_isNotEditable( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$fingerprintView = $this->getFingerprintView( $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId, false );

		$this->assertNotContains( '<a ', $html );
	}

	public function testGetHtml_valuesAreEscaped() {
		$fingerprintView = $this->getFingerprintView();
		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setLabel( 'en', '<a href="#">evil html</a>' );
		$fingerprint->setDescription( 'en', '<script>alert( "xss" );</script>' );
		$fingerprint->setAliasGroup( 'en', array( '<b>bold</b>', '<i>italic</i>' ) );
		$html = $fingerprintView->getHtml( $fingerprint );

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
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( $fingerprint );

		$this->assertContains( 'wb-empty', $html );
	}

	public function testGetHtml_isNotMarkedAsEmpty() {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( $this->getFingerprint() );

		$this->assertNotContains( 'wb-empty', $html );
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetHtml_withEntityId( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$fingerprintView = $this->getFingerprintView( $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId );
		$idString = $entityId->getSerialization();

		$this->assertNotContains( 'id="wb-firstHeading-new"', $html );
		$this->assertContains( 'id="wb-firstHeading-' . $idString . '"', $html );
		$this->assertContains( '(' . $idString . ')', $html );
		$this->assertContains( '<a ', $html );
	}

	public function testGetHtml_withoutEntityId() {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( Fingerprint::newEmpty() );

		$this->assertContains( 'id="wb-firstHeading-new"', $html );
		$this->assertNotContains( 'id="wb-firstHeading-Q', $html );
		$this->assertNotContains( '(new)', $html );
		$this->assertNotContains( '<a ', $html );
	}

	public function testGetHtml_containsAliasesLabel() {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( $this->getFingerprint() );

		$this->assertContains( 'A.&thinsp;k.&thinsp;a.:', $html );
		$this->assertContains( 'strong', $html, 'make sure the setUp works' );
		$this->assertNotContains( '<strong class="test">', $html );
	}

	/**
	 * @dataProvider emptyFingerprintProvider
	 */
	public function testGetHtml_containsIsEmptyPlaceholders( Fingerprint $fingerprint, $message ) {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( $fingerprint );

		$this->assertContains( $message, $html );
		$this->assertContains( 'strong', $html, 'make sure the setUp works' );
		$this->assertNotContains( '<strong class="test">', $html );
	}

}
