<?php

namespace Wikibase\Repo\Tests\Specials;

use Wikibase\CopyrightMessageBuilder;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Specials\SpecialSetAliases;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Specials\SpecialSetAliases
 * @covers \Wikibase\Repo\Specials\SpecialModifyTerm
 * @covers \Wikibase\Repo\Specials\SpecialModifyEntity
 * @covers \Wikibase\Repo\Specials\SpecialWikibaseRepoPage
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
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetAliasesTest extends SpecialModifyTermTestCase {

	protected function newSpecialPage() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$copyrightView = new SpecialPageCopyrightView( new CopyrightMessageBuilder(), '', '' );

		return new SpecialSetAliases(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	}

	public function testGivenItemWithAliasContainingPipeCharacter_editingResultsTriggersError() {
		$item = new Item();
		$languageCode = 'en';
		$item->setAliases( $languageCode, [ 'piped|alias' ] );

		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$store->saveEntity( $item, __METHOD__, $this->getTestUser()->getUser(), EDIT_NEW );

		$id = $item->getId();

		$editRequest = new \FauxRequest( [ 'id' => $id, 'language' => $languageCode, 'value' => 'test' ], true );

		list( $output, ) = $this->executeSpecialPage(
			$id->getSerialization() . '/' . $languageCode,
			$editRequest,
			'qqx'
		);

		$this->assertThatHamcrest( $output, is( htmlPiece( havingChild(
			both( tagMatchingOutline( "<p class='error'/>" ) )
				->andAlso( havingTextContents( '(wikibase-wikibaserepopage-pipe-in-alias)' ) )
		) ) ) );
	}

}
