<?php

namespace Wikibase\View\Tests;

use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\HtmlTermRenderer;
use Wikibase\View\SimpleEntityTermsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;

/**
 * @covers Wikibase\View\SimpleEntityTermsView
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
	use PHPUnit4And6Compat;

	private function getEntityTermsView( $editSectionCalls = 0, TermsListView $termsListView = null ) {
		$editSectionGenerator = $this->getMock( EditSectionGenerator::class );
		$editSectionGenerator->expects( $this->exactly( $editSectionCalls ) )
			->method( 'getLabelDescriptionAliasesEditSection' )
			->will( $this->returnValue( '<EDITSECTION>' ) );

		$textProvider = new DummyLocalizedTextProvider( 'lkt' );

		$termsListView = $termsListView ?: $this->getMockBuilder( TermsListView::class )
			->disableOriginalConstructor()
			->getMock();

		$htmlTermRenderer = $this->getMock( HtmlTermRenderer::class );
		$htmlTermRenderer->method( 'renderTerm' )
			->will( $this->returnCallback( function( Term $term ) {
				return htmlspecialchars( $term->getText() );
			} ) );

		$labelDescriptionLookup = $this->getMock( LabelDescriptionLookup::class );
		$labelDescriptionLookup->method( 'getLabel' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				$terms = [
					'Q111' => new Term( 'language', '<LABEL>' ),
					'Q666' => new Term( 'language', '<a href="#">evil html</a>' ),
				];
				return isset( $terms[ $entityId->getSerialization() ] ) ? $terms[ $entityId->getSerialization() ] : null;
			} ) );
		$labelDescriptionLookup->method( 'getDescription' )
			->will( $this->returnCallback( function( EntityId $entityId ) {
				return $entityId->getSerialization() === 'Q111' ? new Term( 'language', '<DESCRIPTION>' ) : null;
			} ) );

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
		$entityTermsView = $this->getEntityTermsView( 1 );
		$aliasGroups = $this->getAliasGroupList( [ '<ALIAS1>', '<ALIAS2>' ] );
		$html = $entityTermsView->getHtml( 'en', new TermList(), new TermList(), $aliasGroups, null );

		$this->assertContains( '&lt;ALIAS1&gt;', $html );
		$this->assertContains( '&lt;ALIAS2&gt;', $html );
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

		$this->assertContains( '<EDITSECTION>', $html );
	}

	public function testGetHtml_valuesAreEscaped() {
		$descriptions = $this->getTermList( '<script>alert( "xss" );</script>' );
		$aliasGroups = $this->getAliasGroupList( [ '<a href="#">evil html</a>', '<b>bold</b>', '<i>italic</i>' ] );

		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( 'en', new TermList(), $descriptions, $aliasGroups, null );

		$this->assertContains( 'evil html', $html, 'make sure it works' );
		$this->assertNotContains( 'href="#"', $html );
		$this->assertNotContains( '<script>', $html );
		$this->assertNotContains( '<b>', $html );
		$this->assertNotContains( '<i>', $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

	public function testGetHtml_isMarkedAsEmptyValue() {
		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( 'en', new TermList(), new TermList() );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-description-empty)', $html );
		$this->assertNotContains( 'wikibase-entitytermsview-heading-aliases', $html );
	}

	public function testGetHtml_isNotMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView( 1 );
		$terms = $this->getTermList( 'not empty' );
		$aliasGroups = $this->getAliasGroupList( [ 'not empty' ] );
		$html = $entityTermsView->getHtml( 'en', $terms, $terms, $aliasGroups, new ItemId( 'Q111' ) );

		$this->assertNotContains( 'wb-empty', $html );
		$this->assertNotContains( '(wikibase-description-empty)', $html );
		$this->assertContains( 'wikibase-entitytermsview-aliases', $html );
	}

	public function testGetHtml_containsEmptyDescriptionPlaceholder() {
		$view = $this->getEntityTermsView( 1 );
		$labels = $this->getTermList( 'not empty' );
		$descriptions = new TermList();
		$aliasGroups = $this->getAliasGroupList( [ 'not empty' ] );
		$html = $view->getHtml( 'en', $labels, $descriptions, $aliasGroups, null );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-description-empty)', $html );
		$this->assertContains( 'wikibase-entitytermsview-aliases', $html );
	}

	public function testGetHtml_containsEmptyAliasesList() {
		$view = $this->getEntityTermsView( 1 );
		$html = $view->getHtml( 'en', new TermList(), new TermList(), new AliasGroupList() );

		$this->assertContains( '<div class="wikibase-entitytermsview-heading-aliases wb-empty"></div>', $html );
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
		$termsListView = $this->getMockBuilder( TermsListView::class )
			->disableOriginalConstructor()
			->getMock();
		$termsListView->expects( $this->once() )
			->method( 'getHtml' )
			->with(
				$labels,
				$descriptions,
				$aliases,
				$this->equalTo( $languageCode === 'de' ? [ 'de', 'en' ] : [ 'en' ] )
			)
			->will( $this->returnValue( '<TERMSLISTVIEW>' ) );
		$entityTermsView = $this->getEntityTermsView( 1, $termsListView );
		$html = $entityTermsView->getHtml( $languageCode, $labels, $descriptions, $aliases, $entityId );

		$this->assertContains( '<TERMSLISTVIEW>', $html );
	}

	public function testGetTitleHtml_withEntityId() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( new ItemId( 'Q111' ) );

		$this->assertContains( '(parentheses: Q111)', $html );
		$this->assertContains( '&lt;LABEL&gt;', $html );
	}

	public function testGetTitleHtml_withoutEntityId() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( null );

		$this->assertNotContains( '(parentheses', $html );
		$this->assertNotContains( '&lt;LABEL&gt;', $html );
	}

	public function testGetTitleHtml_labelIsEscaped() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( new ItemId( 'Q666' ) );

		$this->assertContains( 'evil html', $html, 'make sure it works' );
		$this->assertNotContains( 'href="#"', $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

	public function testGetTitleHtml_isMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( null );

		$this->assertContains( 'wb-empty', $html );
		$this->assertContains( '(wikibase-label-empty)', $html );
	}

	public function testGetTitleHtml_isNotMarkedAsEmpty() {
		$entityTermsView = $this->getEntityTermsView( 0 );
		$html = $entityTermsView->getTitleHtml( new ItemId( 'Q111' ) );

		$this->assertNotContains( 'wb-empty', $html );
		$this->assertNotContains( '(wikibase-label-empty)', $html );
	}

}
