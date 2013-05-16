<?php
namespace Wikibase\Test;

use Wikibase\SettingsArray;

/**
 * Tests for ApiClientInfo module.
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
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Database
 * @group API
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseClient
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ApiClientInfoTest extends \ApiTestCase {

	/**
	 * @dataProvider settingsProvider
	 */
	public function testGetUrlInfo( SettingsArray $settings ) {
		$data = $this->doApiRequest(
			array(
				'action' => 'query',
				'meta' => 'wikibase',
				'wbprop' => 'url',
			)
		);

		$this->assertArrayHasKey( 'query', $data[0] );
		$this->assertArrayHasKey( 'wikibase', $data[0]['query'] );
		$this->assertArrayHasKey( 'repo', $data[0]['query']['wikibase'] );
		$this->assertArrayHasKey( 'url', $data[0]['query']['wikibase']['repo'] );

		$urlInfo = $data[0]['query']['wikibase']['repo']['url'];

		$this->assertArrayHasKey( 'base', $urlInfo );
		$this->assertArrayHasKey( 'scriptpath', $urlInfo );
		$this->assertArrayHasKey( 'articlepath', $urlInfo );

		$this->assertTrue( is_string( $urlInfo['base'] ) );
		$this->assertTrue( is_string( $urlInfo['scriptpath'] ) );
		$this->assertTrue( is_string( $urlInfo['articlepath'] ) );

		$this->assertEquals( $settings->getSetting( 'repoUrl' ), $urlInfo['base'] );
		$this->assertEquals( $settings->getSetting( 'repoScriptPath' ), $urlInfo['scriptpath'] );
		$this->assertEquals( $settings->getSetting( 'repoArticlePath' ), $urlInfo['articlepath'] );

	}

	public function settingsProvider() {
		$settings = array(
			'repoUrl' => 'http://www.example.org',
			'repoScriptPath' => '/w',
			'repoArticlePath' => '/wiki/$1'
		);

		return array(
			array( new SettingsArray( $settings ) )
		);
	}

}
