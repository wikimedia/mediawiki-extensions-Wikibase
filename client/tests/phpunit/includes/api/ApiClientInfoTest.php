<?php

namespace Wikibase\Test;

use ApiMain;
use ApiQuery;
use FauxRequest;
use User;
use Wikibase\ApiClientInfo;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\ApiClientInfo
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

	/**
	 * @var \ApiTestContext
	 */
	protected $apiContext;

	protected function setUp() {
		parent::setUp();

		$this->apiContext = new \ApiTestContext();
	}

	/**
	 * @param array $params
	 * @param SettingsArray $settings
	 *
	 * @return ApiClientInfo
	 */
	public function getApiModule( array $params, SettingsArray $settings ) {
		$request = new FauxRequest( $params, true );

		$user = User::newFromName( 'zombie' );

		$context = $this->apiContext->newTestContext( $request, $user );
		$apiMain = new ApiMain( $context, true );
		$apiQuery = new ApiQuery( $apiMain, 'wikibase' );

		$apiModule = new ApiClientInfo( $apiQuery, 'query' );
		$apiModule->setSettings( $settings );

		return $apiModule;
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

		$this->assertInternalType( 'string', $urlInfo['base'],
			"The repo URL information for 'base' should be a string" );
		$this->assertInternalType( 'string', $urlInfo['scriptpath'],
			"The repo URL information for 'scriptpath' should be a string" );
		$this->assertInternalType( 'string', $urlInfo['articlepath'],
			"The repo URL information for 'articlepath' should be a string" );

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
