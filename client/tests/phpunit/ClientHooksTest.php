<?php
 /**
 *
 * Copyright © 02.07.13 by the authors listed below.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @license GPL 2+
 * @file
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group WikibaseClient
 *
 * @author Daniel Kinzler
 */


namespace Wikibase\Test;


use Wikibase\ClientHooks;
use Wikibase\SettingsArray;

/**
 * Class ClientHooksTest
 * @covers Wikibase\ClientHooks
 * @package Wikibase\Test
 */
class ClientHooksTest extends \PHPUnit_Framework_TestCase {

	public static function provideOnSetupAfterCache() {
		return array(
			array( // #0: no local repo, all values set
				array( // $settings
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBName' => 'mw_mywiki',
				),
				false, // $repoIsLocal
				array( // $expected
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
				)
			),

			array( // #1: no local repo, no values set
				array( // $settings
					'repoUrl' => null,
					'repoArticlePath' => null,
					'repoScriptPath' => null,
					'siteGlobalID' => null,
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBName' => 'mw_mywiki',
				),
				false, // $repoIsLocal
				array( // $expected
					'repoUrl' => null,
					'repoArticlePath' => null,
					'repoScriptPath' => null,
					'siteGlobalID' => 'mw_mywiki',
				)
			),

			array( // #2: local repo, all values set
				array( // $settings
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBName' => 'mw_mywiki',
				),
				false, // $repoIsLocal
				array( // $expected
					'repoUrl' => 'http://acme.com',
					'repoArticlePath' => '/wiki',
					'repoScriptPath' => '/w',
					'siteGlobalID' => 'mywiki',
				)
			),

			array( // #3: local repo, no values set
				array( // $settings
					'repoUrl' => null,
					'repoArticlePath' => null,
					'repoScriptPath' => null,
					'siteGlobalID' => null,
				),
				array( // $wg
					'wgServer' => 'http://www.acme.com',
					'wgArticlePath' => '/mywiki',
					'wgScriptPath' => '/mediawiki',
					'wgDBName' => 'mw_mywiki',
				),
				false, // $repoIsLocal
				array( // $expected
					'repoUrl' => 'http://www.acme.com',
					'repoArticlePath' => '/mywiki',
					'repoScriptPath' => '/mediawiki',
					'siteGlobalID' => 'mw_mywiki',
				)
			),
		);
	}

	/**
	 * @dataProvider provideOnSetupAfterCache
	 */
	public function testOnSetupAfterCache( array $settings, array $wg, $repoIsLocal, $expected ) {
		$settings = new SettingsArray( $settings );
		ClientHooks::applyMagicDefaultSettings( $settings, $wg, $repoIsLocal );

		foreach ( $expected as $key => $value ) {
			$this->assertEquals( $value, $settings->getSetting( $key ), "Setting $key" );
		}
	}

}