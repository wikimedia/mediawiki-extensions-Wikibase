<?php
declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\ServiceWiring;

use Wikibase\Client\Tests\Unit\ServiceWiringTestCase;
use Wikibase\Lib\SettingsArray;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LangLinkSiteGroupTest extends ServiceWiringTestCase {

	public function testReturnsSetting(): void {
		$langListGroup = 'test';

		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'languageLinkSiteGroup' => $langListGroup,
			] )
		);

		$this->assertSame(
			$langListGroup,
			$this->getService( 'WikibaseClient.LangLinkSiteGroup' )
		);
	}

	public function testFallsBackToSiteGroup(): void {
		$siteGroup = 'test';

		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'languageLinkSiteGroup' => null,
			] )
		);

		$this->mockService(
			'WikibaseClient.SiteGroup',
			$siteGroup
		);

		$this->assertSame(
			$siteGroup,
			$this->getService( 'WikibaseClient.LangLinkSiteGroup' )
		);
	}

}
