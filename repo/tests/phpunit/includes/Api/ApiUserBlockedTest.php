<?php
/**
 * Created by IntelliJ IDEA.
 * User: migr
 * Date: 13.12.18
 * Time: 11:18
 */

namespace Wikibase\Repo\Tests\Api;

use ApiUsageException;
use Block;

/**
 * @covers \Wikibase\Repo\Api
 *
 * @license GPL-2.0-or-later
 *
 * @group API
 * @group WikibaseAPI
 * @group medium
 */
class ApiUserBlockedTest extends WikibaseApiTestCase {

	/** @var Block */
	private $block;

	// FIXME: make this class based setup?
	protected function setUp() {
		parent::setUp();

		$testuser = self::getTestUser()->getUser();
		$this->block = new Block( [
			'address' => $testuser,
			'reason' => 'testing in ' . __CLASS__,
			'by' => $testuser->getId(),
		] );
		$this->block->insert();
	}

	protected function tearDown() {
		parent::tearDown();
		$this->block->delete();
	}

	public function testBlock() {
		$testuser = self::getTestUser()->getUser();

		$this->assertTrue( $testuser->isBlocked() );

		$editData = [];

		try {
			$this->doApiRequestWithToken( [
				'action' => 'wbeditentity',
				'new' => 'item',
				'data' => json_encode( $editData ),
			], null, $testuser );
			$this->fail( 'Expected apierror-writeapidenied to be raised' );
		} catch ( ApiUsageException $exception ) {
			$message = $exception->getMessageObject();
			$this->assertTrue( $exception->getStatusValue()->hasMessage( 'wikibase-api-failed-save' ) );
			$this->assertTrue( $exception->getStatusValue()->hasMessage( 'blockedtext' ) );
			$this->assertTrue( $exception->getStatusValue()->hasMessage( 'no-permission' ) );
		}
	}

}
