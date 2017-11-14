<?php

namespace Wikibase\View\Tests;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
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
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
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
		$languageDirectionalityLookup->method( 'getDirectionality' )
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

	private function getTermList( $term, $languageCode = 'en' ) {
		return new TermList( [ new Term( $languageCode, $term ) ] );
	}

	public function getTermsListViewProvider() {
		$languageCode = 'arc';
		$labels = $this->getTermList( '<LABEL>', $languageCode );
		$descriptions = $this->getTermList( '<DESCRIPTION>', $languageCode );
		$aliasGroups = new AliasGroupList();
		$aliasGroups->setAliasesForLanguage( $languageCode, [ '<ALIAS1>', '<ALIAS2>' ] );

		return [
			[
				$labels, $descriptions, $aliasGroups, $languageCode, true, true, true
			],
			[
				new TermList(), new TermList(), new AliasGroupList(), 'lkt', false, false, false
			],
			[
				new TermList(),
				new TermList(),
				new AliasGroupList(),
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
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups,
		$languageCode,
		$hasLabel,
		$hasDescription,
		$hasAliases
	) {
		$languageDirectionality = $languageCode === 'arc' ? 'rtl' : 'ltr';
		$view = $this->getTermsListView( 1 );
		$html = $view->getHtml( $labels, $descriptions, $aliasGroups, [ $languageCode ] );

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
		$textProvider->method( 'get' )
			->will( $this->returnCallback( function( $key ) {
				return $key === 'wikibase-entitytermsforlanguagelistview-language' ? '"RAW"' : "($key)";
			} ) );

		$view = $this->getTermsListView( 0, $textProvider );
		$html = $view->getHtml( new TermList(), new TermList(), new AliasGroupList(), [] );

		$this->assertContains( '&quot;RAW&quot;', $html );
		$this->assertNotContains( '"RAW"', $html );
	}

	public function testGetTermsListView_noAliasesProvider() {
		$labels = $this->getTermList( '<LABEL>' );
		$descriptions = $this->getTermList( '<DESCRIPTION>' );
		$view = $this->getTermsListView( 1 );
		$html = $view->getHtml( $labels, $descriptions, null, [ 'en' ] );

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
