<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
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

		$languageDirectionalityLookup = $this->getMock( LanguageDirectionalityLookup::class );
		$languageDirectionalityLookup->expects( $this->any() )
			->method( 'getDirectionality' )
			->will( $this->returnCallback( function( $languageCode ) {
				return [
					'en' => 'ltr',
					'arc' => 'rtl',
					'qqx' => 'ltr'
				][ $languageCode ];
			} ) );

		return new TermsListView(
			TemplateFactory::getDefaultInstance(),
			$languageNameLookup,
			$textProvider ?: new DummyLocalizedTextProvider(),
			$languageDirectionalityLookup
		);
	}

	private function getFingerprint( $languageCode = 'en' ) {
		$fingerprint = new Fingerprint();
		$fingerprint->setLabel( $languageCode, '<LABEL>' );
		$fingerprint->setDescription( $languageCode, '<DESCRIPTION>' );
		$fingerprint->setAliasGroup( $languageCode, array( '<ALIAS1>', '<ALIAS2>' ) );
		return $fingerprint;
	}

	public function getTermsListViewProvider() {
		$item = new Item(
			new ItemId( 'Q1' ),
			$this->getFingerprint( 'arc' )
		);
		return [
			[
				$item, 'arc', true, true, true
			],
			[
				new Item(), 'lkt', false, false, false
			],
			[
				new Item(
					new ItemId( 'Q1' ),
					new Fingerprint()
				),
				'en',
				false,
				false,
				false
			]
		];
	}

	/**
	 * @dataProvider getTermsListViewProvider
	 */
	public function testGetTermsListView(
		EntityDocument $entity,
		$languageCode,
		$hasLabel,
		$hasDescription,
		$hasAliases
	) {
		$languageDirectionality = $languageCode === 'arc' ? 'rtl' : 'ltr';
		$view = $this->getTermsListView( 1 );
		$html = $view->getHtml( $entity, $entity, $entity, [ $languageCode ] );

		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-language)', $html );
		$this->assertContains( 'wikibase-entitytermsforlanguageview-' . $languageCode, $html );
		$this->assertContains( '&lt;LANGUAGENAME-' . $languageCode . '&gt;', $html );

		if ( !$hasLabel || !$hasDescription || !$hasAliases ) {
			$this->assertContains( 'wb-empty', $html );
		}
		if ( $hasLabel ) {
			$this->assertContains(
				'class="wikibase-labelview " dir="' . $languageDirectionality . '" lang="' . $languageCode . '"',
				$html
			);
			$this->assertNotContains( '(wikibase-label-empty)', $html );
			$this->assertContains( '&lt;LABEL&gt;', $html );
		} else {
			$this->assertContains( 'class="wikibase-labelview wb-empty" dir="ltr" lang="qqx"', $html );
			$this->assertContains( '(wikibase-label-empty)', $html );
		}

		if ( $hasDescription ) {
			$this->assertContains(
				'class="wikibase-descriptionview " dir="' . $languageDirectionality . '" lang="' . $languageCode . '"',
				$html
			);
			$this->assertNotContains( '(wikibase-description-empty)', $html );
			$this->assertContains( '&lt;DESCRIPTION&gt;', $html );
		} else {
			$this->assertContains( 'class="wikibase-descriptionview wb-empty" dir="ltr" lang="qqx"', $html );
			$this->assertContains( '(wikibase-description-empty)', $html );
		}

		if ( $hasAliases ) {
			$this->assertContains( '&lt;ALIAS1&gt;', $html );
			$this->assertContains( '&lt;ALIAS2&gt;', $html );
			$this->assertContains(
				'class="wikibase-aliasesview-list" dir="' . $languageDirectionality . '" lang="' . $languageCode . '"',
				$html
			);
		}

		// List headings
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-label)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-description)', $html );
		$this->assertContains( '(wikibase-entitytermsforlanguagelistview-aliases)', $html );

		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

	public function testGetTermsListView_isEscaped() {
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

	public function testGetTermsListView_noAliasesProvider() {
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
