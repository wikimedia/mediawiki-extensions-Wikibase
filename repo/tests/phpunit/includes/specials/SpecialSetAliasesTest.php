<?php

namespace Wikibase\Test;

use Wikibase\Repo\Specials\SpecialSetAliases;

/**
 * @covers Wikibase\Repo\Specials\SpecialSetAliases
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
class SpecialSetAliasesTest extends SpecialModifyTermTestCase {

	protected function newSpecialPage() {
		return new SpecialSetAliases();
	}

}
