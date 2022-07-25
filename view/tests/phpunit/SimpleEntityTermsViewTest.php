<?php

namespace Wikibase\View\Tests;

use HamcrestPHPUnitIntegration;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\HtmlTermRenderer;
use Wikibase\View\SimpleEntityTermsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;

/**
 * @covers \Wikibase\View\SimpleEntityTermsView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 * @uses Wikibase\View\TermsListView
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Thiemo Kreuz
 */
class SimpleEntityTermsViewTest extends \PHPUnit\Framework\TestCase {
	use HamcrestPHPUnitIntegration;

	private function getEntityTermsView( $editSectionCalls = 0, TermsListView $termsListView = null ) {
		$editSectionGenerator = $this->createMock( EditSectionGenerator::class );
		$editSectionGenerator->expects( $this->exactly( $editSectionCalls ) )
			->method( 'getLabelDescriptionAliasesEditSection' )
			->willReturn( '<EDITSECTION>' );

		$textProvider = new DummyLocalizedTextProvider( 'lkt' );

		$termsListView = $termsListView ?: $this->createMock( TermsListView::class );

		$htmlTermRenderer = $this->createMock( HtmlTermRenderer::class );
		$htmlTermRenderer->method( 'renderTerm' )
			->willReturnCallback( function( Term $term ) {
				return htmlspecialchars( $term->getText() );
			} );

		$labelDescriptionLookup = $this->createMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->method( 'getLabel' )
			->willReturnCallback( function( EntityId $entityId ) {
				$terms = [
					'Q111' => new Term( 'language', '<LABEL>' ),
					'Q666' => new Term( 'language', '<a href="#">evil html</a>' ),
				];
				return $terms[$entityId->getSerialization()] ?? null;
			} );
		$labelDescriptionLookup->method( 'getDescription' )
			->willReturnCallback( function( EntityId $entityId ) {
				return $entityId->getSerialization() === 'Q111' ? new Term( 'language', '<DESCRIPTION>' ) : null;
			} );

		return new SimpleEntityTermsView(
			$htmlTermRenderer,
			$labelDescriptionLookup,
			TemplateFactory::getDefaultInstance(),
			$editSectionGenerator,
			$termsListView,
			$textProvider
		);
	}

	private function getTermList( $term, $languageCode = 'en' ) {
		return new TermList( [ new Term( $languageCode, $term ) ] );
	}

	private function getAliasGroupList( array $aliases, $languageCode = 'en' ) {
		return new AliasGroupList( [ new AliasGroup( $languageCode, $aliases ) ] );
	}

	public function testGetHtml_containsAliases() {
		$alias1 = '<ALIAS1>';
		$alias2 = '<ALIAS2>';
		$entityTermsView = $this->getEntityTermsView( 1 );
		$aliasGroups = $this->getAliasGroupList( [ $alias1, $alias2 ] );
		$html = $entityTermsView->getHtml( 'en', new TermList(), new TermList(), $aliasGroups, null );

		$matchingAliasMarkup = tagMatchingOutline(
			'<li class="wikibase-entitytermsview-aliases-alias" data-aliases-separator="(wikibase-aliases-separator)">'
		);
		$this->assertThatHamcrest( $html, is( htmlPiece(
			both( havingChild( both( $matchingAliasMarkup )->andAlso( havingTextContents( $alias1 ) ) ) )
				->andAlso(
					havingChild( both( $matchingAliasMarkup )->andAlso( havingTextContents( $alias2 ) ) )
				)
		) ) );
	}

	public function entityFingerprintProvider() {
		$labels = $this->getTermList( '<LABEL>' );
		$descriptions = $this->getTermList( '<DESCRIPTION>' );
		$emptyAliases = new AliasGroupList();

		return [
			'empty' => [ new TermList(), new TermList(), $emptyAliases, new ItemId( 'Q42' ), 'en' ],
			'other language' => [ $labels, $descriptions, $emptyAliases, new ItemId( 'Q42' ), 'de' ],
			'other id' => [ $labels, $descriptions, $emptyAliases, new ItemId( 'Q12' ), 'en' ],
		];
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetHtml_isEditable(
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliasGroups,
		ItemId $entityId,
		$languageCode
	) {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$html = $entityTermsView->getHtml( $languageCode, $labels, $descriptions, $aliasGroups, $entityId );

		$this->assertStringContainsString( '<EDITSECTION>', $html );
	}

	public function testGetHtml_valuesAreEscaped() {
		$descriptions = $this->getTermList( '<script>alert( "xss" );</script>' );
		$aliasGroups = $this->getAliasGroupList( [ '<a href="#">evil html</a>', '<b>bold</b>', '<i>italic</i>' ] );

		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( 'en', new TermList(), $descriptions, $aliasGroups, null );

		$this->assertStringContainsString( 'evil html', $html, 'make sure it works' );
		$this->assertStringNotContainsString( 'href="#"', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<b>', $html );
		$this->assertStringNotContainsString( '<i>', $html );
		$this->assertStringNotContainsString( '&amp;', $html, 'no double escaping' );
	}

	public function testGetHtml_isMarkedAsEmptyValue() {
		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( 'en', new TermList(), new TermList() );

		$this->assertStringContainsString( 'wb-empty', $html );
		$this->assertStringContainsString( '(wikibase-description-empty)', $html );
		$this->assertStringNotContainsString( 'wikibase-entitytermsview-heading-aliases', $html );
	}

	public function testGetHtml_isNotMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$terms = $this->getTermList( 'not empty' );
		$aliasGroups = $this->getAliasGroupList( [ 'not empty' ] );
		$html = $entityTermsView->getHtml( 'en', $terms, $terms, $aliasGroups, new ItemId( 'Q111' ) );

		$this->assertStringNotContainsString( 'wb-empty', $html );
		$this->assertStringNotContainsString( '(wikibase-description-empty)', $html );
		$this->assertStringContainsString( 'wikibase-entitytermsview-aliases', $html );
	}

	public function testGetHtml_containsEmptyDescriptionPlaceholder() {
		$view = $this->getEntityTermsView( 1 );
		$labels = $this->getTermList( 'not empty' );
		$descriptions = new TermList();
		$aliasGroups = $this->getAliasGroupList( [ 'not empty' ] );
		$html = $view->getHtml( 'en', $labels, $descriptions, $aliasGroups, null );

		$this->assertStringContainsString( 'wb-empty', $html );
		$this->assertStringContainsString( '(wikibase-description-empty)', $html );
		$this->assertStringContainsString( 'wikibase-entitytermsview-aliases', $html );
	}

	public function testGetHtml_containsEmptyAliasesList() {
		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( 'en', new TermList(), new TermList(), new AliasGroupList() );

		$this->assertStringContainsString( '<div class="wikibase-entitytermsview-heading-aliases wb-empty"></div>', $html );
	}

	/**
	 * @dataProvider entityFingerprintProvider
	 */
	public function testGetHtml_containsAllTerms(
		TermList $labels,
		TermList $descriptions,
		AliasGroupList $aliases,
		ItemId $entityId,
		$languageCode
	) {
		$termsListView = $this->createMock( TermsListView::class );
		$termsListView->expects( $this->once() )
			->method( 'getHtml' )
			->with(
				$labels,
				$descriptions,
				$aliases,
				$languageCode === 'de' ? [ 'de', 'en' ] : [ 'en' ]
			)
			->willReturn( '<TERMSLISTVIEW>' );
		$entityTermsView = $this->getEntityTermsView( 1, $termsListView );
		$html = $entityTermsView->getHtml( $languageCode, $labels, $descriptions, $aliases, $entityId );

		$this->assertStringContainsString( '<TERMSLISTVIEW>', $html );
	}

	public function testGetTitleHtml_withEntityId() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( new ItemId( 'Q111' ) );

		$this->assertStringContainsString( '(parentheses: Q111)', $html );
		$this->assertStringContainsString( '&lt;LABEL&gt;', $html );
	}

	public function testGetTitleHtml_withoutEntityId() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( null );

		$this->assertStringNotContainsString( '(parentheses', $html );
		$this->assertStringNotContainsString( '&lt;LABEL&gt;', $html );
	}

	public function testGetTitleHtml_labelIsEscaped() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( new ItemId( 'Q666' ) );

		$this->assertStringContainsString( 'evil html', $html, 'make sure it works' );
		$this->assertStringNotContainsString( 'href="#"', $html );
		$this->assertStringNotContainsString( '&amp;', $html, 'no double escaping' );
	}

	public function testGetTitleHtml_isMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( null );

		$this->assertStringContainsString( 'wb-empty', $html );
		$this->assertStringContainsString( '(wikibase-label-empty)', $html );
	}

	public function testGetTitleHtml_isNotMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( new ItemId( 'Q111' ) );

		$this->assertStringNotContainsString( 'wb-empty', $html );
		$this->assertStringNotContainsString( '(wikibase-label-empty)', $html );
	}

}
