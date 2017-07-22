<?php

namespace Wikibase\Repo\Tests\Specials;

use FauxRequest;
use Language;
use Message;
use SpecialPageTestBase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\EntityContent;
use Wikibase\Repo\WikibaseRepo;

/**
 * Test case for modify term special pages
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
abstract class SpecialModifyTermTestCase extends SpecialPageTestBase {

	use HtmlAssertionHelpers;

	const USER_LANGUAGE = 'en';

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'read' => true, 'edit' => true, 'item-term' => true ] ] );
	}

	/**
	 * Creates a new item and returns its id.
	 *
	 * @param string $language
	 * @param string $termValue
	 *
	 * @return string
	 */
	private function createNewItemWithTerms( $language, $termValue ) {
		$item = new Item();
		// add data and check if it is shown in the form
		$item->setLabel( $language, $termValue );
		$item->setDescription( $language, $termValue );
		$item->setAliases( $language, [ $termValue ] );

		// save the item
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, "testing", $GLOBALS['wgUser'], EDIT_NEW | EntityContent::EDIT_IGNORE_CONSTRAINTS );

		// return the id
		return $item->getId()->getSerialization();
	}

	public function testRenderWithoutSubPage_AllInputFieldsPresent() {
		list( $output, ) = $this->executeSpecialPage( '', null, self::USER_LANGUAGE );

		$expectedLanguage = self::USER_LANGUAGE;
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='id'/>" )
		) ) ) );
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='language' value='$expectedLanguage'/>" )
		) ) ) );
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='value'/>" )
		) ) ) );

		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testRenderWithOneSubpageValue_TreatsValueAsItemIdAndShowsOnlyTermInputField() {
		$notUserLanguage = 'de';
		$id = $this->createNewItemWithTerms( $notUserLanguage, 'some-term-value' );

		list( $output, ) = $this->executeSpecialPage( $id, null, self::USER_LANGUAGE );

		$expectedLanguage = self::USER_LANGUAGE;
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='id' type='hidden' value='$id'/>" )
		) ) ) );
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='language' type='hidden' value='$expectedLanguage'/>" )
		) ) ) );
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='value'/>" )
		) ) ) );
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='remove' value='remove' type='hidden'/>" )
		) ) ) );

		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testRenderWithTwoSubpageValues_TreatsSecondValueAsLanguageAndShowsOnlyTermInputField() {
		$id = $this->createNewItemWithTerms( $itemTermLanguage = 'de', $termValue = 'foo' );

		// execute with two subpage values
		list( $output, ) = $this->executeSpecialPage( $id . '/' . $itemTermLanguage, null, self::USER_LANGUAGE );

		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='id' type='hidden' value='$id'/>" )
		) ) ) );
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='language' type='hidden' value='$itemTermLanguage'/>" )
		) ) ) );
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='value' value='$termValue'/>" )
		) ) ) );
		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='remove' value='remove' type='hidden'/>" )
		) ) ) );

		$this->assertHtmlContainsSubmitControl( $output );
	}

	public function testValuePreservesWhenNothingEntered() {
		$id = $this->createNewItemWithTerms( $language = 'de', $termValue = 'foo' );

		$request = new FauxRequest( [ 'id' => $id, 'language' => $language, 'value' => '' ], true );

		list( $output, ) = $this->executeSpecialPage( '', $request );

		assertThat( $output, is( htmlPiece( havingChild(
			tagMatchingOutline( "<input name='value' value='$termValue'/>" )
		) ) ) );
	}

	public function testGivenUserHasInsufficientPermissions_errorIsShown() {
		$this->setMwGlobals( 'wgGroupPermissions', [ '*' => [ 'read' => true, 'edit' => true, 'item-term' => false ] ] );

		$id = $this->createNewItemWithTerms( $language = 'de', $termValue = 'foo' );

		$request = new FauxRequest( [ 'id' => $id, 'language' => $language, 'value' => 'test' ], true );

		list( $output, ) = $this->executeSpecialPage( '', $request, self::USER_LANGUAGE );

		assertThat( $output, is( htmlPiece( havingChild(
			both( tagMatchingOutline( "<p class='error'/>" ) )
			->andAlso( havingTextContents( new Message( 'permissionserrors', [], new Language( self::USER_LANGUAGE ) ) ) )
		) ) ) );
	}

}
