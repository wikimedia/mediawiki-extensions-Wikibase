<?php
namespace Wikibase\Test;

use Wikibase\SettingsArray;
use Wikibase\ApiClientInfo;

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
class ApiClientInfoTest extends \MediaWikiTestCase {

	protected $apiContext;

	protected $user;

	public function setUp() {
		parent::setUp();

		$this->apiContext = new \ApiTestContext();

		$userName = 'zombie';

		$this->user = \User::newFromName( $userName );

		if ( !$this->user->getID() ) {
            $this->user = \User::createNew( $userName, array(
                "email" => "zombie@example.com",
                "real_name" => "Zombie" ) );
        }
	}

	public function getApiModule( array $params, SettingsArray $settings ) {
		$request = new \FauxRequest( $params, true );
		$context = $this->apiContext->newTestContext( $request, $this->user );
		$apiMain = new \ApiMain( $context, true );

		return new ApiClientInfo( $apiMain, 'query', $settings );
	}

	/**
	 * @dataProvider executeProvider
	 */
	public function testExecute( $params ) {
		$settings = $this->getSettings();

		$module = $this->getApiModule( $params, $settings );
		$module->execute();

		$result = $module->getResult()->getData();

		$this->assertInternalType( 'array', $result, 'top level element is an array' );

		$this->assertArrayHasKey( 'query', $result, 'top level element has a query key' );
		$this->assertArrayHasKey( 'wikibase', $result['query'], 'second level element has a wikibase key' );
	}

	/**
	 * @dataProvider getRepoInfoProvider
	 */
	public function testGetRepoInfo( array $params, SettingsArray $settings ) {
		$module = $this->getApiModule( $params, $settings );
		$reqParams = $module->extractRequestParams();
		$repoInfo = $module->getRepoInfo( $reqParams );

		$this->assertArrayHasKey( 'repo', $repoInfo, 'top level element has repo key' );
		$urlInfo = $repoInfo['repo']['url'];

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

	public function executeProvider() {
		$params = $this->getApiRequestParams();

		return array(
			array( $params )
		);
	}

	public function getRepoInfoProvider() {
		$params = $this->getApiRequestParams();
		$settings = $this->getSettings();

		return array(
			array( $params, $settings )
		);
	}

	/**
	 * @return array
	 */
	protected function getApiRequestParams() {
		$params = array(
			'action' => 'query',
			'meta' => 'wikibase',
			'wbprop' => 'url'
		);

		return $params;
	}

	/**
	 * @return SettingsArray
	 */
	protected function getSettings() {
		return new SettingsArray( array(
			'repoUrl' => 'http://www.example.org',
			'repoScriptPath' => '/w',
			'repoArticlePath' => '/wiki/$1'
		) );
	}

}
