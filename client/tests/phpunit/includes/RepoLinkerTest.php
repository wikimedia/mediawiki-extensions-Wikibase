<?php

namespace Wikibase\Test;
use Wikibase\RepoLinker;
use Wikibase\EntityId;
use Wikibase\Item;

/**
 * @covers Wikibase\RepoLinker
 *
 * @since 0.4
 *
 * @group WikibaseClient
 * @group RepoLinkerTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoLinkerTest extends \MediaWikiTestCase {

	public function getRepoSettings() {
		return array(
			array(
				'baseUrl' => '//www.example.com',
				'articlePath' => '/wiki/$1',
				'scriptPath' => '',
				'repoNamespaces' => array(
					'wikibase-item' => '',
					'wikibase-property' => 'Property'
				)
			),
			array(
				'baseUrl' => '//example.com',
				'articlePath' => '/wiki/$1',
				'scriptPath' => '',
				'repoNamespaces' => array(
					'wikibase-item' => '',
					'wikibase-property' => 'Property'
				)
			),
			array(
				'baseUrl' => 'http://www.example.com',
				'articlePath' => '/wiki/$1',
				'scriptPath' => '/w',
				'repoNamespaces' => array(
					'wikibase-item' => 'Item',
					'wikibase-property' => 'Property'
				)
			)
		);
	}

	public function baseUrlProvider() {
		$settings = $this->getRepoSettings();

		return array(
			array(
				'//www.example.com',
				$settings[0]
			),
			array(
				'//example.com',
				$settings[1]
			),
			array(
				'http://www.example.com',
				$settings[2]
			),
		);
	}

	public function namespaceProvider() {
		$settings = $this->getRepoSettings();

		return array(
			array(
				'',
				$settings[0],
				'item'
			),
			array(
				'Property',
				$settings[1],
				'property'
			),
			array(
				'Item',
				$settings[2],
				'item'
			)
		);
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testGetNamespace( $expected, array $settings, $entityType ) {
		$repoLinker = new RepoLinker(
			$settings['baseUrl'],
			$settings['articlePath'],
			$settings['scriptPath'],
			$settings['repoNamespaces']
		);

		$namespace = $repoLinker->getNamespace( $entityType );

		$this->assertEquals( $expected, $namespace );
	}

}
