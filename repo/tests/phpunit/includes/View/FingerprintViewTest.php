<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
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
 * @group Database
 *		^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class FingerprintViewTest extends \MediaWikiLangTestCase {

	public function provideTestGetHtml() {
		$cases = array();

		$fingerprint = Fingerprint::newEmpty();

		$cases['empty fingerprint'] = array(
			$fingerprint,
			new ItemId( 'Q42' ),
			'en'
		);

		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setLabel( new Term( 'en', 'Foobar' ) );
		$fingerprint->setDescription( new Term( 'en', 'This is a foo bar.' ) );
		$fingerprint->setAliasGroup( new AliasGroup( 'en', array( 'foo', 'bar' ) ) );

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

		$fingerprint = Fingerprint::newEmpty();
		$fingerprint->setLabel( new Term( 'de', '<a href="#">evil html</a>' ) );
		$fingerprint->setDescription( new Term( 'de', '<script>alert( "xss" );</script>' ) );
		$fingerprint->setAliasGroup( new AliasGroup( 'de', array( '<b>bold</b>', '<i>italic</i>' ) ) );

		$cases['html escaping'] = array(
			$fingerprint,
			new ItemId( 'Q42' ),
			'de'
		);

		return $cases;
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtmlEditable( Fingerprint $fingerprint, EntityId $entityId, $languageCode ) {
		$fingerprintView = new FingerprintView( new SectionEditLinkGenerator(), $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId, true );
		$serializedId = $entityId->getSerialization();

		$this->assertContains( $serializedId, $html );

		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetLabel/' . $serializedId . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );
		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetDescription/' . $serializedId . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );
		$this->assertRegExp( '@<a href="[^"]*\bSpecial:SetAliases/' . $serializedId . '/' . $languageCode . '"[^>]*>\S+</a>@', $html );

		$hasLabel = $fingerprint->getLabels()->hasTermForLanguage( $languageCode );
		if ( $hasLabel ) {
			$label = $fingerprint->getLabel( $languageCode )->getText();
			$this->assertContains( htmlspecialchars( $label ), $html );
		} else {
			$this->assertRegExp( '@class="[^"]*wb-value-empty@', $html );
		}

		$hasDescription = $fingerprint->getDescriptions()->hasTermForLanguage( $languageCode );
		if ( $hasDescription ) {
			$description = $fingerprint->getDescription( $languageCode )->getText();
			$this->assertContains( htmlspecialchars( $description ), $html );
		} else {
			$this->assertRegExp( '@class="[^"]*wb-value-empty@', $html );
		}

		$hasAliases = $fingerprint->getAliasGroups()->hasGroupForLanguage( $languageCode );
		if ( $hasAliases ) {
			$aliases = $fingerprint->getAliasGroup( $languageCode )->getAliases();
			foreach ( $aliases as $alias ) {
				$this->assertContains( htmlspecialchars( $alias ), $html );
			}
			$this->assertNotRegExp( '@class="[^"]*wb-aliases-empty@', $html );
		} else {
			$this->assertRegExp( '@class="[^"]*wb-value-empty@', $html );
			$this->assertRegExp( '@class="[^"]*wb-aliases-empty@', $html );
		}

		if ( $hasLabel && $hasDescription && $hasAliases ) {
			$this->assertNotRegExp( '@class="[^"]*wb-value-empty@', $html );
		}
	}

	/**
	 * @dataProvider provideTestGetHtml
	 */
	public function testGetHtmlNotEditable( Fingerprint $fingerprint, EntityId $entityId, $languageCode ) {
		$fingerprintView = new FingerprintView( new SectionEditLinkGenerator(), $languageCode );
		$html = $fingerprintView->getHtml( $fingerprint, $entityId, false );

		$this->assertNotContains( '<a ', $html );
		$this->assertContains( $entityId->getSerialization(), $html );
	}

	public function testGetHtmlNoEntityId() {
		$fingerprintView = new FingerprintView( new SectionEditLinkGenerator(), 'en' );
		$html = $fingerprintView->getHtml( Fingerprint::newEmpty(), null, true );

		$this->assertNotContains( '<a ', $html );
		$this->assertContains( 'new', $html );
	}

}
