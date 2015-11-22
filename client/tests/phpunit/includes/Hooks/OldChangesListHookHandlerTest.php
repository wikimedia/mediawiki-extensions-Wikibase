<?php

namespace Wikibase\Client\Tests\Hooks;

use OldChangesList;
use RequestContext;
use TestRecentChangesHelper;
use Wikibase\Client\Hooks\OldChangesListHookHandler;

/**
 * @covers Wikibase\Client\Hooks\OldChangesListHookHandler
 *
 * @group WikibaseClientHooks
 * @group WikibaseClient
 * @group Wikibase
 */
class OldChangesListHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var TestRecentChangesHelper
	 */
	private $testRecentChangesHelper;

	public function __construct() {
		parent::__construct();

		$this->testRecentChangesHelper = new TestRecentChangesHelper();
	}

	public function testOnOldChangesListRecentChangesLine() {
		$changesList = new OldChangesList( RequestContext::getMain() );

		$recentChange = $this->testRecentChangesHelper->makeEditRecentChange(
			$user,
			'Cat',
			'20131103212153',
			5,
			191,
			190,
			0,
			0
		);

		OldChangesListHookHandler::onOldChangesListRecentChangesLine(
			$changesList,
			'it is a string!',
			$recentChange,
			array()
		);
	}

}
