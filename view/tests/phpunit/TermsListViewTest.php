<?php

namespace Wikibase\View\Tests;

use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\LanguageDirectionalityLookup;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;

/**
 * @covers \Wikibase\View\TermsListView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class TermsListViewTest extends \PHPUnit\Framework\TestCase {

	private function getTermsListView(
		$languageNameCalls = 0,
		LocalizedTextProvider $textProvider = null
	) {
		$languageNameLookup = $this->createMock( LanguageNameLookup::class );
		$languageNameLookup->expects( $this->exactly( $languageNameCalls ) )
			->method( 'getName' )
			->willReturnCallback( function( $languageCode ) {
				return "<LANGUAGENAME-$languageCode>";
			} );

		$languageDirectionalityLookup = $this->createMock( LanguageDirectionalityLookup::class );
		$languageDirectionalityLookup->method( 'getDirectionality' )
			->willReturnCallback( function( $languageCode ) {
				return [
					'en' => 'ltr',
					'arc' => 'rtl',
					'qqx' => 'ltr',
				][ $languageCode ];
			} );

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
				$labels, $descriptions, $aliasGroups, $languageCode, true, true, true,
			],
			[
				new TermList(), new TermList(), new AliasGroupList(), 'lkt', false, false, false,
			],
			[
				new TermList(),
				new TermList(),
				new AliasGroupList(),
				'en',
				false,
				false,
				false,
			],
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

		$this->assertStringContainsString( '(wikibase-entitytermsforlanguagelistview-language)', $html );
		$this->assertStringContainsString( 'wikibase-entitytermsforlanguageview-' . $languageCode, $html );
		$this->assertStringContainsString( '&lt;LANGUAGENAME-' . $languageCode . '&gt;', $html );

		if ( !$hasLabel || !$hasDescription || !$hasAliases ) {
			$this->assertStringContainsString( 'wb-empty', $html );
		}
		if ( $hasLabel ) {
			$this->assertStringContainsString(
				'class="wikibase-labelview " dir="' . $languageDirectionality . '" lang="' . $languageCode . '"',
				$html
			);
			$this->assertStringNotContainsString( '(wikibase-label-empty)', $html );
			$this->assertStringContainsString( '&lt;LABEL&gt;', $html );
		} else {
			$this->assertStringContainsString( 'class="wikibase-labelview wb-empty" dir="ltr" lang="qqx"', $html );
			$this->assertStringContainsString( '(wikibase-label-empty)', $html );
		}

		if ( $hasDescription ) {
			$this->assertStringContainsString(
				'class="wikibase-descriptionview " dir="' . $languageDirectionality . '" lang="' . $languageCode . '"',
				$html
			);
			$this->assertStringNotContainsString( '(wikibase-description-empty)', $html );
			$this->assertStringContainsString( '&lt;DESCRIPTION&gt;', $html );
		} else {
			$this->assertStringContainsString( 'class="wikibase-descriptionview wb-empty" dir="ltr" lang="qqx"', $html );
			$this->assertStringContainsString( '(wikibase-description-empty)', $html );
		}

		if ( $hasAliases ) {
			$this->assertStringContainsString( '&lt;ALIAS1&gt;', $html );
			$this->assertStringContainsString( '&lt;ALIAS2&gt;', $html );
			$this->assertStringContainsString(
				'class="wikibase-aliasesview-list" dir="' . $languageDirectionality . '" lang="' . $languageCode . '"',
				$html
			);
		}

		// List headings
		$this->assertStringContainsString( '(wikibase-entitytermsforlanguagelistview-label)', $html );
		$this->assertStringContainsString( '(wikibase-entitytermsforlanguagelistview-description)', $html );
		$this->assertStringContainsString( '(wikibase-entitytermsforlanguagelistview-aliases)', $html );

		$this->assertStringNotContainsString( '&amp;', $html, 'no double escaping' );
	}

	public function testGetTermsListView_noAliasesProvider() {
		$labels = $this->getTermList( '<LABEL>' );
		$descriptions = $this->getTermList( '<DESCRIPTION>' );
		$view = $this->getTermsListView( 1 );
		$html = $view->getHtml( $labels, $descriptions, null, [ 'en' ] );

		$this->assertStringContainsString( '(wikibase-entitytermsforlanguagelistview-language)', $html );
		$this->assertStringContainsString( '(wikibase-entitytermsforlanguagelistview-label)', $html );
		$this->assertStringContainsString( '(wikibase-entitytermsforlanguagelistview-description)', $html );
		$this->assertStringContainsString( '(wikibase-entitytermsforlanguagelistview-aliases)', $html );

		$this->assertStringContainsString( 'wikibase-entitytermsforlanguageview-en', $html );
		$this->assertStringContainsString( '&lt;LANGUAGENAME-en&gt;', $html );
		$this->assertStringContainsString( '&lt;LABEL&gt;', $html );
		$this->assertStringContainsString( '&lt;DESCRIPTION&gt;', $html );
		$this->assertStringNotContainsString( '&lt;ALIAS1&gt;', $html );
		$this->assertStringNotContainsString( '&lt;ALIAS2&gt;', $html );
		$this->assertStringNotContainsString( '&amp;', $html, 'no double escaping' );
	}

}
