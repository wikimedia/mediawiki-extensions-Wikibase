<?php

namespace Wikibase\Test;
use Wikibase\RepoLinker;
use Wikibase\EntityId;
use Wikibase\Item;

/**
 * Tests for the Wikibase\RepoLinker class.
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

	/**
	 * @dataProvider baseUrlProvider
	 */
	public function testBaseUrl( $expected, array $settings ) {
		$repoLinker = new RepoLinker(
			$settings['baseUrl'],
			$settings['articlePath'],
			$settings['scriptPath'],
			$settings['repoNamespaces']
		);

		$baseUrl = $repoLinker->baseUrl();
		$this->assertEquals( $expected, $baseUrl );
	}

	public function repoArticleUrlProvider() {
		$settings = $this->getRepoSettings();

		return array(
			array(
				'//www.example.com/wiki/Rome',
				$settings[0],
				'Rome'
			),
			array(
				'//example.com/wiki/Rome',
				$settings[1],
				'Rome'
			),
			array(
				'//www.example.com/wiki/Hall_%26_Oates',
				$settings[0],
				'Hall & Oates'
			),
			array(
				'http://www.example.com/wiki/Why%3F_(film)',
				$settings[2],
				'Why? (film)'
			)
		);
	}

	/**
	 * @dataProvider repoArticleUrlProvider
	 */
	 public function testRepoArticleUrl( $expected, array $settings, $page ) {
		$repoLinker = new RepoLinker(
			$settings['baseUrl'],
			$settings['articlePath'],
			$settings['scriptPath'],
			$settings['repoNamespaces']
		);

		$repoUrl = $repoLinker->repoArticleUrl( $page );

		$this->assertEquals( $expected, $repoUrl );
	}

	public function repoItemUrlProvider() {
		$settings = $this->getRepoSettings();

		return array(
			array(
				'//www.example.com/wiki/Q4',
				$settings[0],
				new EntityId( Item::ENTITY_TYPE, 4 )
			),
			array(
				'//example.com/wiki/Q100',
				$settings[1],
				new EntityId( Item::ENTITY_TYPE, 100 )
			),
			array(
				'http://www.example.com/wiki/Item:Q100',
				$settings[2],
				new EntityId( Item::ENTITY_TYPE, 100 )
			)
		);
	}

	/**
	 * @dataProvider repoItemUrlProvider
	 */
	public function testRepoItemUrl( $expected, array $settings, EntityId $entityId ) {
		$repoLinker = new RepoLinker(
			$settings['baseUrl'],
			$settings['articlePath'],
			$settings['scriptPath'],
			$settings['repoNamespaces']
		);

		$itemUrl = $repoLinker->repoItemUrl( $entityId );

		$this->assertEquals( $expected, $itemUrl );
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

	public function repoLinkProvider() {
		$settings = $this->getRepoSettings();

		return array(
			array(
				'<a class="plainlinks" href="//www.example.com/api.php?action=query&amp;meta=siteinfo">api query</a>',
				$settings[0],
				array(
					'target' => null,
					'text' => 'api query',
					'params' => array(
						'query' => array(
							'params' => array(
								'action' => 'query',
								'meta' => 'siteinfo'
							),
							'type' => 'api'
						)
					)
				)
			),
			array(
				'<a class="plainlinks" href="//example.com/index.php?title=Rome">Roma</a>',
				$settings[1],
				array(
					'target' => 'Rome',
					'text' => 'Roma',
					'params' => array(
						'query' => array(
							'params' => array(
								'title' => 'Rome'
							),
							'type' => 'index'
						)
					)
				)
			),
			array(
				'<a class="plainlinks" href="http://www.example.com/wiki/Rome">Rome</a>',
				$settings[2],
				array(
					'target' => 'Rome',
					'text' => 'Rome',
					'params' => array()
				)
			)
		);
	}

	/**
	 * @dataProvider repoLinkProvider
	 */
	public function testRepoLink( $expected, $settings, $params ) {
		$repoLinker = new RepoLinker(
			$settings['baseUrl'],
			$settings['articlePath'],
			$settings['scriptPath'],
			$settings['repoNamespaces']
		);

		$repoLink = $repoLinker->repoLink( $params['target'], $params['text'], $params['params'] );

		$this->assertEquals( $expected, $repoLink );
	}

}
