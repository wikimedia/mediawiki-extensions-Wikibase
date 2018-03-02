<?php

namespace Wikibase\Client\Tests;

use Wikibase\Client\WikibaseClient;

/**
 * @covers Wikibase\Client\WikibaseClient
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 */
class WikibaseClientRepositorySettingsTest extends \MediaWikiTestCase {

	public function testGivenOldRepositorySettings_individualSettingsAreUsedForLocalRepo() {
		$clientSettings = $this->getDefaultSettings();

		$clientSettings['repoDatabase'] = 'foodb';
		$clientSettings['entityNamespaces'] = [ 'item' => 303, 'property' => 808 ];
		$clientSettings['repoConceptBaseUri'] = 'http://foo.oof/entity/';

		$this->setMwGlobals( 'wgWBClientSettings', $clientSettings );
		$this->setMwGlobals( 'wgHooks', [] );

		$client = WikibaseClient::getDefaultInstance( 'reset' );
		$repositoryDefinitions = $client->getRepositoryDefinitions();

		$this->assertEquals(
			[ '' => 'foodb' ],
			$repositoryDefinitions->getDatabaseNames()
		);
		$this->assertEquals(
			[ 'item' => 303, 'property' => 808 ],
			$repositoryDefinitions->getEntityNamespaces()
		);
		$this->assertEquals(
			[ '' => 'http://foo.oof/entity/' ],
			$repositoryDefinitions->getConceptBaseUris()
		);
	}

	public function testGivenOldForeignRepositorySettings_oldSettingsAreUsedForForeignRepository() {
		$clientSettings = $this->getDefaultSettings();

		$clientSettings['foreignRepositories'] = [
			'coolrepo' => [
				'repoDatabase' => 'cooldb',
				'entityNamespaces' => [ 'cool' => 666 ],
				'baseUri' => 'http://soooo.cooooool/entity/',
				'prefixMapping' => [ '' => '' ],
			]
		];
		$clientSettings['repoDatabase'] = 'foodb';
		$clientSettings['entityNamespaces'] = [ 'item' => 303, 'property' => 808 ];
		$clientSettings['repoConceptBaseUri'] = 'http://foo.oof/entity/';

		$this->setMwGlobals( 'wgWBClientSettings', $clientSettings );
		$this->setMwGlobals( 'wgHooks', [] );

		$client = WikibaseClient::getDefaultInstance( 'reset' );
		$repositoryDefinitions = $client->getRepositoryDefinitions();

		$this->assertEquals(
			[ '' => 'foodb', 'coolrepo' => 'cooldb' ],
			$repositoryDefinitions->getDatabaseNames()
		);
		$this->assertEquals(
			[ 'item' => 303, 'property' => 808, 'cool' => 666 ],
			$repositoryDefinitions->getEntityNamespaces()
		);
		$this->assertEquals(
			[ '' => 'http://foo.oof/entity/', 'coolrepo' => 'http://soooo.cooooool/entity/' ],
			$repositoryDefinitions->getConceptBaseUris()
		);
	}

	public function testGivenOnlyRepositorySettingPresent_theSettingIsUsedToDefineRepositories() {
		$clientSettings = $this->getDefaultSettings();

		$clientSettings['repositories'] = [
			'' => [
				'repoDatabase' => 'foodb',
				'entityNamespaces' => [ 'item' => 303, 'property' => 808 ],
				'baseUri' => 'http://foo.oof/entity/',
				'prefixMapping' => [ '' => '' ],
			],
			'coolrepo' => [
				'repoDatabase' => 'cooldb',
				'entityNamespaces' => [ 'cool' => 666 ],
				'baseUri' => 'http://soooo.cooooool/entity/',
				'prefixMapping' => [ '' => '' ],
			]
		];
		unset( $clientSettings['repoDatabase'] );
		unset( $clientSettings['entityNamespaces'] );
		unset( $clientSettings['repoConceptBaseUri'] );
		unset( $clientSettings['foreignRepositories'] );

		$this->setMwGlobals( 'wgWBClientSettings', $clientSettings );
		$this->setMwGlobals( 'wgHooks', [] );

		$client = WikibaseClient::getDefaultInstance( 'reset' );
		$repositoryDefinitions = $client->getRepositoryDefinitions();

		$this->assertEquals(
			[ '' => 'foodb', 'coolrepo' => 'cooldb' ],
			$repositoryDefinitions->getDatabaseNames()
		);
		$this->assertEquals(
			[ 'item' => 303, 'property' => 808, 'cool' => 666 ],
			$repositoryDefinitions->getEntityNamespaces()
		);
		$this->assertEquals(
			[ '' => 'http://foo.oof/entity/', 'coolrepo' => 'http://soooo.cooooool/entity/' ],
			$repositoryDefinitions->getConceptBaseUris()
		);
	}

	public function testGivenCustomEntityTypeAssignedToParticularRepo_entityNamespaceOnlyDefinedForThisRepo() {
		$clientSettings = $this->getDefaultSettings();

		$clientSettings['repositories'] = [
			'' => [
				'repoDatabase' => 'foodb',
				'entityNamespaces' => [ 'item' => 303, 'property' => 808 ],
				'baseUri' => 'http://foo.oof/entity/',
				'prefixMapping' => [ '' => '' ],
			],
			'coolrepo' => [
				'repoDatabase' => 'cooldb',
				'entityNamespaces' => [ 'cool' => 666 ],
				'baseUri' => 'http://soooo.cooooool/entity/',
				'prefixMapping' => [ '' => '' ],
			]
		];

		$this->setMwGlobals( 'wgWBClientSettings', $clientSettings );
		$this->setMwGlobals( 'wgHooks', [
			'WikibaseEntityNamespaces' => [
				function ( &$namespaces ) {
					$namespaces['cool'] = 666;
				},
			]
		] );

		$client = WikibaseClient::getDefaultInstance( 'reset' );
		$repositoryDefinitions = $client->getRepositoryDefinitions();

		$this->assertEquals( 666, $repositoryDefinitions->getEntityNamespaces()['cool'] );
		$this->assertEquals( [ 'cool' ], $repositoryDefinitions->getEntityTypesPerRepository()['coolrepo'] );
	}

	public function testGivenNoRepoAssignedForCustomEntityType_entityNamespaceOnlyDefinedForLocalRepo() {
		$clientSettings = $this->getDefaultSettings();

		$clientSettings['repositories'] = [
			'' => [
				'repoDatabase' => 'foodb',
				'entityNamespaces' => [ 'item' => 303, 'property' => 808 ],
				'baseUri' => 'http://foo.oof/entity/',
				'prefixMapping' => [ '' => '' ],
			],
		];

		$this->setMwGlobals( 'wgWBClientSettings', $clientSettings );
		$this->setMwGlobals( 'wgHooks', [
			'WikibaseEntityNamespaces' => [
				function ( &$namespaces ) {
					$namespaces['cool'] = 666;
				},
			]
		] );

		$client = WikibaseClient::getDefaultInstance( 'reset' );
		$repositoryDefinitions = $client->getRepositoryDefinitions();

		$this->assertEquals( 666, $repositoryDefinitions->getEntityNamespaces()['cool'] );
		$this->assertEquals( [ 'item', 'property', 'cool' ], $repositoryDefinitions->getEntityTypesPerRepository()[''] );
	}

	private function getDefaultSettings() {
		return array_merge(
			require __DIR__ . '/../../../../lib/config/WikibaseLib.default.php',
			require __DIR__ . '/../../../config/WikibaseClient.default.php'
		);
	}

}
