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
class LangLinkSiteGroupsTest extends ServiceWiringTestCase {

	public function testReturnsSetting(): void {
		$langListGroups = [ 'test', 'foo' ];

		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'languageLinkAllowedSiteGroups' => $langListGroups,
			] )
		);

		$this->assertSame(
			$langListGroups,
			$this->getService( 'WikibaseClient.LangLinkSiteGroups' )
		);
	}

	public function testReturnsSettingFallbackToMainGroup(): void {
		$langListGroup = 'test';

		$this->mockService(
			'WikibaseClient.Settings',
			new SettingsArray( [
				'languageLinkAllowedSiteGroups' => null,
			] )
		);

		$this->mockService(
			'WikibaseClient.LangLinkSiteGroup',
			$langListGroup
		);

		$this->assertSame(
			[ $langListGroup ],
			$this->getService( 'WikibaseClient.LangLinkSiteGroups' )
		);
	}

}
