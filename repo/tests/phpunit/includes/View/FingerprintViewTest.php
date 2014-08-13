<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\View\FingerprintView;
use Wikibase\Repo\View\SectionEditLinkGenerator;

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

	private function getFingerprintView( $languageCode = 'en' ) {
		return new FingerprintView( new SectionEditLinkGenerator(), $languageCode );
	}

	private function getFingerprint( $languageCode = 'en' ) {
		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setLabel( new Term( $languageCode, 'Foobar' ) );
		$fingerprint->setDescription( new Term( $languageCode, 'This is a foo bar.' ) );
		$fingerprint->setAliasGroup( new AliasGroup( $languageCode, array( 'foo', 'bar' ) ) );
		return $fingerprint;
	}

	public function entityProvider() {
		$cases = array();

		$cases['empty fingerprint'] = array(
			Fingerprint::newEmpty(),
			new ItemId( 'Q42' ),
			'en'
		);

		$fingerprint = $this->getFingerprint();

		$cases['empty fingerprint'] = array(
			$fingerprint,
			new ItemId( 'Q42' ),
			'en'
		);

		$cases['other language'] = array(
			$fingerprint,
			new ItemId( 'Q42' ),
			'de'
		);

		$cases['other item id'] = array(
			$fingerprint,
			new ItemId( 'Q12' ),
			'en'
		);

		return $cases;
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testGetHtmlEditable( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$fingerprintView = $this->getFingerprintView( $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId );
		$idString = $entityId->getSerialization();

		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetLabel/' . $idString . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );
		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetDescription/' . $idString . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );
		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetAliases/' . $idString . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testGetHtmlNotEditable( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$fingerprintView = $this->getFingerprintView( $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId, false );

		$this->assertNotContains( '<a ', $html );
	}

	public function emptyFingerprintProvider() {
		$noLabel = $this->getFingerprint();
		$noLabel->removeLabel( 'en' );

		$noDescription = $this->getFingerprint();
		$noDescription->removeDescription( 'en' );

		$noAliases = $this->getFingerprint();
		$noAliases->removeAliasGroup( 'en' );

		return array(
			'empty' => array( Fingerprint::newEmpty() ),
			'no label' => array( $noLabel ),
			'no description' => array( $noDescription ),
			'no aliases' => array( $noAliases ),
		);
	}

	/**
	 * @dataProvider emptyFingerprintProvider
	 */
	public function testGetHtmlEmpty( Fingerprint $fingerprint ) {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( $fingerprint );

		$this->assertContains( 'wb-value-empty', $html );
	}

	public function testGetHtmlNotEmpty() {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( $this->getFingerprint() );

		$this->assertNotContains( 'wb-value-empty', $html );
	}

	/**
	 * @dataProvider entityProvider
	 */
	public function testGetHtmlEntityId( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$fingerprintView = $this->getFingerprintView( $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId );
		$idString = $entityId->getSerialization();

		$this->assertNotContains( 'id="wb-firstHeading-new"', $html );
		$this->assertContains( 'id="wb-firstHeading-' . $idString . '"', $html );
		$this->assertRegExp( '@<h1[^>]*>\s*<span[^>]*>\s*'
			. '<span class="wb-value\s*"[^>]*>[^<]*</span>\s*'
			. '<span class="wb-value-supplement">(' . $idString . ')</span>@', $html );
		$this->assertContains( '<a ', $html );
	}

	public function testGetHtmlNoEntityId() {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( Fingerprint::newEmpty() );

		$this->assertContains( 'id="wb-firstHeading-new"', $html );
		$this->assertNotContains( 'id="wb-firstHeading-Q', $html );
		$this->assertNotContains( 'wb-value-supplement', $html );
		$this->assertNotContains( '<a ', $html );
	}

}
