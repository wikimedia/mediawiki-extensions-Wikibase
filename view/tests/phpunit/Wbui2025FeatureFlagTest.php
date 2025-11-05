<?php

declare( strict_types = 1 );

namespace Wikibase\View\Tests;

use MediaWiki\User\User;
use MediaWiki\User\UserOptionsLookup;
use PHPUnit\Framework\TestCase;
use Wikibase\Lib\SettingsArray;
use Wikibase\View\Wbui2025FeatureFlag;

/**
 * @covers \Wikibase\View\Wbui2025FeatureFlag
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 */
class Wbui2025FeatureFlagTest extends TestCase {

	public static function provideOptionCombinations() {
		yield 'expect wbui2025 == true if global feature active' => [ true, false, null, true ];
		yield 'expect wbui2025 == false if feature inactive' => [ false, false, null, false ];
		yield 'expect wbui2025 == true if beta feature active and user opted in' => [ false, true, "1", true ];
		yield 'expect wbui2025 == false if beta feature active and user opted out' => [ false, true, null, false ];
		yield 'expect wbui2025 == false if beta feature inactive and user opted in' => [ false, false, "1", false ];
	}

	/**
	 * @dataProvider provideOptionCombinations
	 */
	public function testWbui2025IfGlobalFeatureActive(
		$wbui2025Enabled,
		$wbui2025BetaFeatureEnabled,
		$userFeatureOptionValue,
		$wbuiFlagValue
	) {
		$userOptionsMock = $this->createConfiguredMock(
			UserOptionsLookup::class,
			[
				'getOption' => $userFeatureOptionValue,
			]
		);
		$flag = new Wbui2025FeatureFlag(
			$userOptionsMock,
			new SettingsArray( [
				'tmpMobileEditingUI' => $wbui2025Enabled,
				'tmpEnableMobileEditingUIBetaFeature' => $wbui2025BetaFeatureEnabled,
			] )
		);
		$user = $this->createMock( User::class );
		$this->assertSame( $wbuiFlagValue, $flag->shouldRenderAsWbui2025( $user ) );
	}

}
