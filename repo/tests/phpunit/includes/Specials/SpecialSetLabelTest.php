<?php

namespace Wikibase\Repo\Tests\Specials;

use Wikibase\Repo\Specials\SpecialSetLabel;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetLabel
 * @covers Wikibase\Repo\Specials\SpecialModifyTerm
 * @covers Wikibase\Repo\Specials\SpecialModifyEntity
 * @covers Wikibase\Repo\Specials\SpecialWikibaseRepoPage
 * @covers Wikibase\Repo\Specials\SpecialWikibasePage
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group SpecialPage
 * @group WikibaseSpecialPage
 *
 * @group Database
 *        ^---- needed because we rely on Title objects internally
 *
 * @licence GNU GPL v2+
 * @author John Erling Blad < jeblad@gmail.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialSetLabelTest extends SpecialModifyTermTestCase {

	protected function newSpecialPage() {
		return new SpecialSetLabel();
	}

}
