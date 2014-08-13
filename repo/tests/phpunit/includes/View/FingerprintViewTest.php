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

	public function fingerprintProvider() {
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
	 * @dataProvider fingerprintProvider
	 */
	public function testGetHtmlEditable( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$fingerprintView = $this->getFingerprintView( $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId, true );
		$serializedId = $entityId->getSerialization();

		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetLabel/' . $serializedId . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );
		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetDescription/' . $serializedId . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );
		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetAliases/' . $serializedId . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );
	}

	public function testGetHtmlNoEmptyValues() {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( $this->getFingerprint(), new ItemId( 'Q3' ), true );

		$this->assertNotContains( 'wb-value-empty', $html );
	}

	/**
	 * @dataProvider fingerprintProvider
	 */
	public function testGetHtmlNotEditable( Fingerprint $fingerprint, ItemId $entityId, $languageCode ) {
		$fingerprintView = $this->getFingerprintView( $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId, false );

		$this->assertNotContains( '<a ', $html );
	}

	public function testGetHtmlNoEntityId() {
		$fingerprintView = $this->getFingerprintView();
		$html = $fingerprintView->getHtml( Fingerprint::newEmpty(), null, true );

		$this->assertNotContains( '<a ', $html );
	}

}
