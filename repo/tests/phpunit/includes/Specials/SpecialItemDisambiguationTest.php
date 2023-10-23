<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Specials;

use InvalidArgumentException;
use MediaWiki\Request\FauxRequest;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\LanguageNameLookupFactory;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\TermIndexEntry;
use Wikibase\Repo\Api\EntitySearchException;
use Wikibase\Repo\Api\EntitySearchHelper;
use Wikibase\Repo\ItemDisambiguation;
use Wikibase\Repo\ItemDisambiguationFactory;
use Wikibase\Repo\Specials\SpecialItemDisambiguation;

/**
 * @covers \Wikibase\Repo\Specials\SpecialItemDisambiguation
 * @covers \Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @license GPL-2.0-or-later
 */
class SpecialItemDisambiguationTest extends SpecialPageTestBase {

	private bool $simulateSearchBackendError;

	protected function setUp(): void {
		parent::setUp();
		$this->simulateSearchBackendError = false;
	}

	private function getMockItemDisambiguationFactory(): ItemDisambiguationFactory {
		$mock = $this->createMock( ItemDisambiguation::class );
		$mock->method( 'getHTML' )
			->willReturnCallback( function ( $searchResult ) {
				return '<span class="mock-span" >ItemDisambiguationHTML-' . count( $searchResult ) . '</span>';
			} );

		return $this->createConfiguredMock( ItemDisambiguationFactory::class,
			[ 'getForLanguage' => $mock ] );
	}

	private function getMockSearchHelper(): EntitySearchHelper {
		$searchResults = [
			[
				'entityId' => new ItemId( 'Q2' ),
				'matchedTermType' => 'label',
				'matchedTerm' => new Term( 'fr', 'Foo' ),
				'displayTerms' => [
					TermIndexEntry::TYPE_DESCRIPTION => new Term( 'en', 'DisplayDescription' ),
				],
			],
			[
				'entityId' => new ItemId( 'Q3' ),
				'matchedTermType' => 'label',
				'matchedTerm' => new Term( 'fr', 'Foo' ),
				'displayTerms' => [
					TermIndexEntry::TYPE_LABEL => new Term( 'en', 'DisplayLabel' ),
				],
			],
		];
		$mock = $this->createMock( EntitySearchHelper::class );

		if ( $this->simulateSearchBackendError ) {
			$mock->method( 'getRankedSearchResults' )
				->willThrowException( new EntitySearchException( \Status::newFatal( 'search-backend-error' ) ) );
		} else {
			$mock->method( 'getRankedSearchResults' )
				->willReturnCallback(
					function( $text, $lang, $entityType ) use ( $searchResults ) {
						if ( $lang !== 'fr' ) {
							throw new InvalidArgumentException( 'Not a valid language code' );
						}

						if ( $text === 'Foo' && $entityType === 'item' ) {
							return $searchResults;
						}

						return [];
					}
				);
		}

		return $mock;
	}

	private function getContentLanguages(): ContentLanguages {
		return new StaticContentLanguages( [ 'ar', 'de', 'en', 'fr' ] );
	}

	private function getMockLanguageNameLookupFactory(): LanguageNameLookupFactory {
		$mock = $this->createMock( LanguageNameLookup::class );
		$mock->method( 'getName' )
			->willReturn( '<LANG>' );

		return $this->createConfiguredMock( LanguageNameLookupFactory::class,
			[ 'getForLanguage' => $mock ] );
	}

	protected function newSpecialPage(): SpecialItemDisambiguation {
		return new SpecialItemDisambiguation(
			$this->getMockSearchHelper(),
			$this->getMockItemDisambiguationFactory(),
			$this->getMockLanguageNameLookupFactory(),
			$this->getContentLanguages()
		);
	}

	public function testForm() {
		[ $html ] = $this->executeSpecialPage();

		$this->assertStringContainsString( '(wikibase-itemdisambiguation-lookup-language)', $html );
		$this->assertStringContainsString( 'name=\'language\'', $html );
		$this->assertStringContainsString( 'id=\'wb-itemdisambiguation-languagename\'', $html );
		$this->assertStringContainsString( 'wb-language-suggester', $html );

		$this->assertStringContainsString( '(wikibase-itemdisambiguation-lookup-label)', $html );
		$this->assertStringContainsString( 'name=\'label\'', $html );
		$this->assertStringContainsString( 'id=\'labelname\'', $html );

		$this->assertStringContainsString( '(wikibase-itemdisambiguation-submit)', $html );
		$this->assertStringContainsString( 'id=\'wb-itembytitle-submit\'', $html );
	}

	public function testRequestParameters() {
		$request = new FauxRequest( [
			'language' => '<LANGUAGE>',
			'label' => '<LABEL>',
		] );
		list( $html, ) = $this->executeSpecialPage( '', $request );

		$this->assertStringContainsString( '&lt;LANGUAGE&gt;', $html );
		$this->assertStringContainsString( '&lt;LABEL&gt;', $html );
		$this->assertStringNotContainsString( '<LANGUAGE>', $html );
		$this->assertStringNotContainsString( '<LABEL>', $html );
		$this->assertStringNotContainsString( '&amp;', $html, 'no double escaping' );
	}

	public function testSubPageParts() {
		list( $html, ) = $this->executeSpecialPage( '<LANGUAGE>/<LABEL>' );

		$this->assertStringContainsString( '&lt;LANGUAGE&gt;', $html );
		$this->assertStringContainsString( '&lt;LABEL&gt;', $html );
	}

	public function testNoLanguage() {
		[ $html ] = $this->executeSpecialPage();

		$this->assertStringNotContainsString( 'mock-span', $html );
	}

	public function testInvalidLanguage() {
		[ $html ] = $this->executeSpecialPage( 'invalid/Foo' );

		$this->assertStringContainsString( '(wikibase-itemdisambiguation-invalid-langcode)', $html );
	}

	public function testSearchBackendError() {
		$this->simulateSearchBackendError = true;
		list( $html, ) = $this->executeSpecialPage( 'fr/Foo' );

		$this->assertStringContainsString( 'search-backend-error', $html );
	}

	public function testNoLabel() {
		[ $html ] = $this->executeSpecialPage( 'fr' );

		$this->assertStringNotContainsString( 'mock-span', $html );
	}

	public function testUnknownLabel() {
		[ $html ] = $this->executeSpecialPage( 'fr/Unknown' );

		$this->assertStringContainsString( 'value=\'fr\'', $html );
		$this->assertStringContainsString( 'value=\'Unknown\'', $html );
		$this->assertStringContainsString( '(wikibase-itemdisambiguation-nothing-found)', $html );
	}

	public function testKnownLabel() {
		[ $html ] = $this->executeSpecialPage( 'fr/Foo' );

		$this->assertStringContainsString( '<span class="mock-span" >ItemDisambiguationHTML-2</span>', $html );
	}

}
