<?php

namespace Wikibase\Repo\Tests\Specials;

use Wikibase\CopyrightMessageBuilder;
use Wikibase\Repo\Specials\SpecialPageCopyrightView;
use Wikibase\Repo\Specials\SpecialSetDescription;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetDescription
 * @covers Wikibase\Repo\Specials\SpecialModifyTerm
 * @covers Wikibase\Repo\Specials\SpecialModifyEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibaseRepoPage
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
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetDescriptionTest extends SpecialModifyTermTestCase {

	protected function newSpecialPage() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$copyrightView = new SpecialPageCopyrightView( new CopyrightMessageBuilder(), '', '' );

		return new SpecialSetDescription(
			$copyrightView,
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->newEditEntityFactory(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	}

}
