<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use InvalidArgumentException;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\Term;
use Wikibase\ItemDisambiguation;
use Wikibase\Lib\Interactors\ConfigurableTermSearchInteractor;
use Wikibase\Lib\Interactors\TermSearchOptions;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\Specials\SpecialItemDisambiguation;
use Wikibase\TermIndexEntry;

/**
 * @covers Wikibase\Repo\Specials\SpecialItemDisambiguation
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
 * @author Daniel Kinzler
 * @author Addshore
 * @author Thiemo Kreuz
 */
class SpecialItemDisambiguationTest extends SpecialPageTestBase {

	/**
	 * @return ItemDisambiguation
	 */
	private function getMockItemDisambiguation() {
		$mock = $this->getMockBuilder( ItemDisambiguation::class )
			->disableOriginalConstructor()
			->getMock();
		$mock->expects( $this->any() )
			->method( 'getHTML' )
			->will( $this->returnCallback( function ( $searchResult ) {
				return '<span class="mock-span" >ItemDisambiguationHTML-' . count( $searchResult ) . '</span>';
			} ) );

		return $mock;
	}

	/**
	 * @return ConfigurableTermSearchInteractor
	 */
	private function getMockSearchInteractor() {
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
		$mock = $this->getMock( ConfigurableTermSearchInteractor::class );

		$mock->expects( $this->any() )
			->method( 'searchForEntities' )
			->will( $this->returnCallback(
				function( $text, $lang, $entityType, array $termTypes ) use ( $searchResults ) {
					if ( $lang !== 'fr' ) {
						throw new InvalidArgumentException( 'Not a valid language code' );
					}

					$expectedTermTypes = [
						TermIndexEntry::TYPE_LABEL,
						TermIndexEntry::TYPE_ALIAS
					];

					if (
						$text === 'Foo' &&
						$entityType === 'item' &&
						$termTypes === $expectedTermTypes
					) {
						return $searchResults;
					}

					return [];
				}
			) );

		$mock->expects( $this->any() )
			->method( 'setTermSearchOptions' )
			->with(
				$this->callback( function ( TermSearchOptions $options ) {
					return $options->getIsCaseSensitive() === false
						&& $options->getIsPrefixSearch() === false
						&& $options->getUseLanguageFallback() === true;
				}
			) );

		return $mock;
	}

	private function getContentLanguages() {
		return new StaticContentLanguages( [ 'ar', 'de', 'en', 'fr' ] );
	}

	/**
	 * @return LanguageNameLookup
	 */
	private function getMockLanguageNameLookup() {
		$mock = $this->getMock( LanguageNameLookup::class );
		$mock->expects( $this->any() )
			->method( 'getName' )
			->will( $this->returnValue( '<LANG>' ) );

		return $mock;
	}

	protected function newSpecialPage() {
		return new SpecialItemDisambiguation(
			$this->getContentLanguages(),
			$this->getMockLanguageNameLookup(),
			$this->getMockItemDisambiguation(),
			$this->getMockSearchInteractor()
		);
	}

	public function testForm() {
		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertContains( '(wikibase-itemdisambiguation-lookup-language)', $html );
		$this->assertContains( 'name=\'language\'', $html );
		$this->assertContains( 'id=\'wb-itemdisambiguation-languagename\'', $html );
		$this->assertContains( 'wb-language-suggester', $html );

		$this->assertContains( '(wikibase-itemdisambiguation-lookup-label)', $html );
		$this->assertContains( 'name=\'label\'', $html );
		$this->assertContains( 'id=\'labelname\'', $html );

		$this->assertContains( '(wikibase-itemdisambiguation-submit)', $html );
		$this->assertContains( 'id=\'wb-itembytitle-submit\'', $html );
	}

	public function testRequestParameters() {
		$request = new FauxRequest( [
			'language' => '<LANGUAGE>',
			'label' => '<LABEL>',
		] );
		list( $html, ) = $this->executeSpecialPage( '', $request );

		$this->assertContains( '&lt;LANGUAGE&gt;', $html );
		$this->assertContains( '&lt;LABEL&gt;', $html );
		$this->assertNotContains( '<LANGUAGE>', $html );
		$this->assertNotContains( '<LABEL>', $html );
		$this->assertNotContains( '&amp;', $html, 'no double escaping' );
	}

	public function testSubPageParts() {
		list( $html, ) = $this->executeSpecialPage( '<LANGUAGE>/<LABEL>' );

		$this->assertContains( '&lt;LANGUAGE&gt;', $html );
		$this->assertContains( '&lt;LABEL&gt;', $html );
	}

	public function testNoLanguage() {
		list( $html, ) = $this->executeSpecialPage( '', null, 'qqx' );

		$this->assertNotContains( 'mock-span', $html );
	}

	public function testInvalidLanguage() {
		list( $html, ) = $this->executeSpecialPage( 'invalid/Foo', null, 'qqx' );

		$this->assertContains( '(wikibase-itemdisambiguation-invalid-langcode)', $html );
	}

	public function testNoLabel() {
		list( $html, ) = $this->executeSpecialPage( 'fr', null, 'qqx' );

		$this->assertNotContains( 'mock-span', $html );
	}

	public function testUnknownLabel() {
		list( $html, ) = $this->executeSpecialPage( 'fr/Unknown', null, 'qqx' );

		$this->assertContains( 'value=\'fr\'', $html );
		$this->assertContains( 'value=\'Unknown\'', $html );
		$this->assertContains( '(wikibase-itemdisambiguation-nothing-found)', $html );
	}

	public function testKnownLabel() {
		list( $html, ) = $this->executeSpecialPage( 'fr/Foo', null, 'qqx' );

		$this->assertContains( '<span class="mock-span" >ItemDisambiguationHTML-2</span>', $html );
	}

}
