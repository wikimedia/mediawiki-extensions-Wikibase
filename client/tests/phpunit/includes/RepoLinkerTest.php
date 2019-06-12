<?php

namespace Wikibase\Client\Tests;

use InvalidArgumentException;
use PHPUnit4And6Compat;
use Wikibase\Client\RepoLinker;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\DataAccessSettingsTest;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;

/**
 * @covers \Wikibase\Client\RepoLinker
 *
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RepoLinkerTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

	private function getRepoSettings() {
		return [
			[
				'baseUrl' => '//www.example.com',
				'conceptBaseUri' => [ '' => 'http://www.example.com/entity' ],
				'articlePath' => '/wiki/$1',
				'scriptPath' => '',
			],
			[
				'baseUrl' => '//example.com/',
				'conceptBaseUri' => [ '' => 'http://example.com/entity' ],
				'articlePath' => '/wiki/$1',
				'scriptPath' => '',
			],
			[
				'baseUrl' => 'http://www.example.com',
				'conceptBaseUri' => [],
				'articlePath' => '/wiki/$1',
				'scriptPath' => '/w',
			]
		];
	}

	private function getRepoLinkerForSettings( array $settings ) {
		return new RepoLinker(
			DataAccessSettingsTest::repositoryPrefixBasedFederation(),
			new EntitySourceDefinitions( [] ),
			$settings['baseUrl'],
			$settings['conceptBaseUri'],
			$settings['articlePath'],
			$settings['scriptPath']
		);
	}

	/**
	 * @dataProvider getEntityTitleProvider
	 */
	public function testGetEntityTitle( $expected, array $settings, EntityId $entityId ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->getEntityTitle( $entityId ) );
	}

	public function getEntityTitleProvider() {
		$itemId = new ItemId( 'Q388' );
		$propertyId = new PropertyId( 'P472' );
		$settings = $this->getRepoSettings();

		return [
			[ 'Special:EntityPage/Q388', $settings[0], $itemId ],
			[ 'Special:EntityPage/P472', $settings[0], $propertyId ]
		];
	}

	/**
	 * @dataProvider getPageUrlProvider
	 */
	public function testGetPageUrl( $expected, array $settings, $page ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->getPageUrl( $page ) );
	}

	public function getPageUrlProvider() {
		$settings = $this->getRepoSettings();

		return [
			[ '//www.example.com/wiki/Cat', $settings[0], 'Cat' ],
			[ 'http://www.example.com/wiki/Frog', $settings[2], 'Frog' ],
			[ '//www.example.com/wiki/Kategorie:Drei%C3%9Figj%C3%A4hriger_Krieg', $settings[0],
				'Kategorie:Dreißigjähriger_Krieg' ],
			[ '//www.example.com/wiki/Why%3F_(American_band)', $settings[0],
				'Why? (American band)' ]
		];
	}

	/**
	 * @dataProvider getPageUrlInvalidProvider
	 */
	public function testGetPageUrlInvalidThrowsException( array $settings, $page ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );
		$this->setExpectedException( InvalidArgumentException::class );
		$repoLinker->getPageUrl( $page );
	}

	public function getPageUrlInvalidProvider() {
		$settings = $this->getRepoSettings();

		return [
			[ $settings[0], [] ]
		];
	}

	/**
	 * @dataProvider formatLinkProvider
	 */
	public function testFormatLink( $expected, array $settings, $url, $text, array $attribs ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->formatLink( $url, $text, $attribs ) );
	}

	public function formatLinkProvider() {
		$settings = $this->getRepoSettings();

		return [
			[
				'<a class="extiw" href="//example.com/wiki/Special:Log/delete">delete</a>',
				$settings[1],
				'//example.com/wiki/Special:Log/delete',
				'delete',
				[]
			],
			[
				'<a tabindex="1" class="extiw" href="http://www.example.com/w/index.php'
					. '?title=Item%3AQ60&amp;diff=prev&amp;oldid=778">diff</a>',
				$settings[2],
				'http://www.example.com/w/index.php?title=Item%3AQ60&diff=prev&oldid=778',
				'diff',
				[
					'tabindex' => 1
				]
			]
		];
	}

	/**
	 * @dataProvider buildEntityLinkProvider
	 */
	public function testBuildEntityLink( $expected, array $settings, EntityId $entityId, array $classes ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->buildEntityLink( $entityId, $classes ) );
	}

	public function buildEntityLinkProvider() {
		$settings = $this->getRepoSettings();

		return [
			[
				'<a class="extiw wb-entity-link" href="//example.com/wiki/Special:EntityPage/Q730">Q730</a>',
				$settings[1],
				new ItemId( 'Q730' ),
				[]
			],
			[
				'<a class="extiw wb-entity-link" href="http://www.example.com/wiki/Special:EntityPage/Q730">Q730</a>',
				$settings[2],
				new ItemId( 'Q730' ),
				[]
			],
			[
				'<a class="extiw wb-entity-link kittens" href="http://www.example.com/wiki/Special:EntityPage/Q730">Q730</a>',
				$settings[2],
				new ItemId( 'Q730' ),
				[ 'kittens' ]
			]
		];
	}

	/**
	 * @dataProvider getEntityUrlProvider
	 */
	public function testGetEntityUrl( $expected, array $settings, EntityId $entityId ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->getEntityUrl( $entityId ) );
	}

	public function getEntityUrlProvider() {
		$settings = $this->getRepoSettings();

		return [
			[
				'//example.com/wiki/Special:EntityPage/Q730',
				$settings[1],
				new ItemId( 'Q730' )
			],
			[
				'http://www.example.com/wiki/Special:EntityPage/Q1234',
				$settings[2],
				new ItemId( 'Q1234' )
			]
		];
	}

	/**
	 * @dataProvider getEntityConceptUri
	 */
	public function testGetEntityConceptUri( $expected, array $settings, EntityId $entityId ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->getEntityConceptUri( $entityId ) );
	}

	public function getEntityConceptUri() {
		$settings = $this->getRepoSettings();

		return [
			[
				'http://www.example.com/entity/Q730',
				$settings[0],
				new ItemId( 'Q730' )
			],
			[
				'http://example.com/entity/Q1234',
				$settings[1],
				new ItemId( 'Q1234' )
			]
		];
	}

	public function testGetEntityConceptUri_entityFromOtherRepository() {
		$settings = $this->getRepoSettings()[0];
		$settings['conceptBaseUri'] = [ '' => 'http://www.example.com/entity', 'foo' => 'http://www.foreign.com/entity' ];

		$linker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( 'http://www.foreign.com/entity/Q111', $linker->getEntityConceptUri( new ItemId( 'foo:Q111' ) ) );
	}

	public function testGivenEntitySourceDefinitions_getEntityConceptUriUsesBasedFromRightSource() {
		$conceptBaseUris = [ 'itemwiki' => 'http://www.itemwiki.com/entity', 'propertywiki' => 'http://www.propertywiki.com/entity' ];

		$linker = new RepoLinker(
			DataAccessSettingsTest::entitySourceBasedFederation(),
			new EntitySourceDefinitions( [
				new EntitySource(
					'itemwiki',
					'itemdb',
					[ 'item' => [ 'namespaceId' => 111, 'slot' => 'main' ] ],
					'http://www.itemwiki.com/entity',
					''
				),
				new EntitySource(
					'propertywiki',
					'propdb',
					[ 'property' => [ 'namespaceId' => 111, 'slot' => 'main' ] ],
					'http://www.itemwiki.com/entity',
					''
				),
			] ),
			'BASE_URI',
			$conceptBaseUris,
			'ARTICLE_PATH',
			'SCRIPT_PATH'
		);

		$this->assertEquals( 'http://www.itemwiki.com/entity/Q111', $linker->getEntityConceptUri( new ItemId( 'Q111' ) ) );
		$this->assertEquals( 'http://www.propertywiki.com/entity/P111', $linker->getEntityConceptUri( new PropertyId( 'P111' ) ) );
	}

	/**
	 * @dataProvider getBaseUrlProvider
	 */
	public function testGetBaseUrl( $expected, array $settings ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->getBaseUrl() );
	}

	public function getBaseUrlProvider() {
		$settings = $this->getRepoSettings();

		return [
			[
				'http://www.example.com',
				$settings[2]
			],
			[
				'//example.com',
				$settings[1]
			]
		];
	}

	/**
	 * @dataProvider getApiUrlProvider
	 */
	public function testGetApiUrl( $expected, array $settings ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->getApiUrl() );
	}

	public function getApiUrlProvider() {
		$settings = $this->getRepoSettings();

		return [
			[
				'http://www.example.com/w/api.php',
				$settings[2]
			]
		];
	}

	/**
	 * @dataProvider getIndexUrlProvider
	 */
	public function testGetIndexUrl( $expected, array $settings ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->getIndexUrl() );
	}

	public function getIndexUrlProvider() {
		$settings = $this->getRepoSettings();

		return [
			[
				'http://www.example.com/w/index.php',
				$settings[2]
			]
		];
	}

	/**
	 * @dataProvider addQueryParamsProvider
	 */
	public function testAddQueryParams( $expected, array $settings, $url, array $params ) {
		$repoLinker = $this->getRepoLinkerForSettings( $settings );

		$this->assertEquals( $expected, $repoLinker->addQueryParams( $url, $params ) );
	}

	public function addQueryParamsProvider() {
		$settings = $this->getRepoSettings();

		return [
			[
				'http://www.example.com/w/api.php?action=query&prop=revisions&titles=Item%3AQ60',
				$settings[2],
				'http://www.example.com/w/api.php',
				[
					'action' => 'query',
					'prop' => 'revisions',
					'titles' => 'Item:Q60'
				]
			],
			[
				'http://www.example.com/w/api.php?action=query&prop=revisions&titles=Q60',
				$settings[2],
				'http://www.example.com/w/api.php',
				[
					'action' => 'query',
					'prop' => 'revisions',
					'titles' => 'Q60'
				]
			]
		];
	}

}
