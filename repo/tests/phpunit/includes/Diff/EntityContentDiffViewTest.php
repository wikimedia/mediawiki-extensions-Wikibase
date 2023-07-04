<?php

namespace Wikibase\Repo\Tests\Diff;

use MediaWikiIntegrationTestCase;
use RequestContext;
use Wikibase\Repo\Diff\EntityContentDiffView;

/**
 * @covers \Wikibase\Repo\Diff\EntityContentDiffView
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thiemo Kreuz
 */
class EntityContentDiffViewTest extends MediaWikiIntegrationTestCase {

	public function testConstructor() {
		new EntityContentDiffView( RequestContext::getMain() );
		$this->assertTrue( true );
	}

}
