<?php

namespace Wikibase\Test\Api;

use Site;
use TestSites;
use Wikibase\Settings;

/**
 * @covers Wikibase\Api\GetSites
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 *
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group GetSitesTest
 *
 * @group Database
 * @group medium
 */
class GetSitesTest extends WikibaseApiTestCase {

	public function testGetSites() {
		$params = array( 'action' => 'wbgetsites' );

		list( $result,, ) = $this->doApiRequest( $params );

		$this->assertArrayHasKey( 'sites', $result );
		$this->assertArrayHasConfiguredSites( $result );
	}

	private function assertArrayHasConfiguredSites( $result ) {
		/** @var Site $site */
		foreach( TestSites::getSites() as $site ) {
			if( in_array( $site->getGroup(), Settings::get( 'siteLinkGroups' ) ) ) {
				$this->assertArrayHasKey( $site->getGlobalId(), $result['sites'] );
				$siteArray = $result['sites'][$site->getGlobalId()];
				$this->assertArrayHasKey( 'globalId', $siteArray );
				$this->assertArrayHasKey( 'domain', $siteArray );
				$this->assertArrayHasKey( 'group', $siteArray );
				$this->assertArrayHasKey( 'language', $siteArray );
				$this->assertEquals( $site->getGlobalId(), $siteArray['globalId'] );
				$this->assertEquals( $site->getDomain(), $siteArray['domain'] );
				$this->assertEquals( $site->getGroup(), $siteArray['group'] );
				$this->assertEquals( $site->getLanguageCode(), $siteArray['language'] );
			}
		}
	}

} 