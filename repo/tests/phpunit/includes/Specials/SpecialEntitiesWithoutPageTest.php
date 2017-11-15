<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use SpecialPageTestBase;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Specials\SpecialEntitiesWithoutPage;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Repo\Specials\SpecialEntitiesWithoutPage
 * @covers Wikibase\Repo\Specials\SpecialWikibaseQueryPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@googlemail.com >
 * @author Addshore
 * @author Thiemo Kreuz
 */
class SpecialEntitiesWithoutPageTest extends SpecialPageTestBase {

	protected function newSpecialPage() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		return new SpecialEntitiesWithoutPage(
			'EntitiesWithoutLabel',
			TermIndexEntry::TYPE_LABEL,
			'wikibase-entitieswithoutlabel-legend',
			$wikibaseRepo->getStore()->newEntitiesWithoutTermFinder(),
			[ 'item', 'property' ],
			new StaticContentLanguages( [ 'acceptedlanguage' ] ),
			new LanguageNameLookup()
		);
	}

	public function testForm() {
		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertContains( '(wikibase-entitieswithoutlabel-label-language)', $html );
		$this->assertContains( 'name=\'language\'', $html );
		$this->assertContains( 'id=\'wb-entitieswithoutpage-language\'', $html );
		$this->assertContains( 'wb-language-suggester', $html );

		$this->assertContains( '(wikibase-entitieswithoutlabel-label-type)', $html );
		$this->assertContains( 'name=\'type\'', $html );
		$this->assertContains( 'id=\'wb-entitieswithoutpage-type\'', $html );
		$this->assertContains( '(wikibase-entity-item)', $html );

		$this->assertContains( '(wikibase-entitieswithoutlabel-submit)', $html );
		$this->assertContains( 'id=\'wikibase-entitieswithoutpage-submit\'', $html );
	}

	public function testRequestParameters() {
		$request = new FauxRequest( [
			'language' => "''LANGUAGE''",
			'type' => "''TYPE''",
		] );
		list( $html, ) = $this->executeSpecialPage( '', $request );

		$this->assertContains( '&#39;&#39;LANGUAGE&#39;&#39;', $html );
		$this->assertContains( '&#39;&#39;TYPE&#39;&#39;', $html );
		$this->assertNotContains( "''LANGUAGE''", $html );
		$this->assertNotContains( "''TYPE''", $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

	public function testSubPageParts() {
		list( $html, ) = $this->executeSpecialPage( "''LANGUAGE''/''TYPE''" );

		$this->assertContains( '&#39;&#39;LANGUAGE&#39;&#39;', $html );
		$this->assertContains( '&#39;&#39;TYPE&#39;&#39;', $html );
	}

	public function testNoParams() {
		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertNotContains( 'class="mw-spcontent"', $html );
		$this->assertNotContains( '(htmlform-invalid-input)', $html );
	}

	public function testNoLanguage() {
		$request = new FauxRequest( [ 'type' => 'item' ] );
		list( $html, ) = $this->executeSpecialPage( '', $request, 'qqx' );

		$this->assertNotContains( 'class="mw-spcontent"', $html );
		$this->assertNotContains( '(htmlform-invalid-input)', $html );
	}

	public function testNoType() {
		list( $html, ) = $this->executeSpecialPage( 'acceptedlanguage', null, 'qqx' );

		$this->assertNotContains( 'class="mw-spcontent"', $html );
		$this->assertNotContains( '(htmlform-invalid-input)', $html );
	}

	public function testInvalidLanguage() {
		list( $html, ) = $this->executeSpecialPage( "''INVALID''", null, 'qqx' );

		$this->assertContains(
			'(wikibase-entitieswithoutlabel-invalid-language: &#39;&#39;INVALID&#39;&#39;)',
			$html
		);
	}

	public function testValidLanguage() {
		$request = new FauxRequest( [ 'type' => 'item' ] );
		list( $html, ) = $this->executeSpecialPage( 'acceptedlanguage', $request, 'qqx' );

		$this->assertContains( 'value=\'acceptedlanguage\'', $html );
		$this->assertContains( 'class="mw-spcontent"', $html );
	}

	public function testInvalidType() {
		list( $html, ) = $this->executeSpecialPage( "acceptedlanguage/''INVALID''", null, 'qqx' );

		$this->assertContains(
			'(wikibase-entitieswithoutlabel-invalid-type: &#39;&#39;INVALID&#39;&#39;)',
			$html
		);
	}

}
