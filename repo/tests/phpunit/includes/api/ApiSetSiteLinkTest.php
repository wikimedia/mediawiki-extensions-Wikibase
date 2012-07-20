<?php

namespace Wikibase\Test;
use ApiTestCase;
use Wikibase\ApiSetSiteLink;

/**
 * Additional tests for ApiLinkSite API module.
 *
 * @group API
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author JOhn Erling Blad < jeblad@gmail.com >
 */
class ApiSetSiteLinkTest extends ApiTestCase {

	public static $jsonData;

	public function setUp() {
		parent::setUp();
		\Wikibase\Utils::insertSitesForTests();
	}

	public function testSetSiteLink() {
		$this->markTestIncomplete( "test the actual API method" ); //TODO
	}
}

