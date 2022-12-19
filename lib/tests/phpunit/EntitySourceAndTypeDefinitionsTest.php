<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\Tests\NewDatabaseEntitySource;
use Wikibase\Lib\EntitySourceAndTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityArticleIdLookup;

/**
 * @covers \Wikibase\Lib\EntitySourceAndTypeDefinitions
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntitySourceAndTypeDefinitionsTest extends TestCase {

	public function testGetServiceArray(): void {
		$callback1 = function () {
			return $this->createStub( EntityArticleIdLookup::class );
		};
		$callback2 = function () {
			return $this->createStub( EntityArticleIdLookup::class );
		};
		$definitions = new EntitySourceAndTypeDefinitions(
			[
				DatabaseEntitySource::TYPE => new EntityTypeDefinitions( [
					'property' => [
						EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK => $callback1,
					],
				] ),
				ApiEntitySource::TYPE => new EntityTypeDefinitions( [
					'property' => [
						EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK => $callback2,
					],
				] ),
			],
			[
				NewDatabaseEntitySource::havingName( 'local' )->build(),
				new ApiEntitySource( 'wikidorta', [ 'property' ], '', '', '', '' ),
			]
		);

		$articleIdLookups = $definitions->getServiceBySourceAndType( EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK );
		$this->assertEquals(
			[
				'local' => [
					'property' => $callback1,
				],
				'wikidorta' => [
					'property' => $callback2,
				],
			],
			$articleIdLookups
		);
	}

	/**
	 * @dataProvider invalidConstructorArgsProvider
	 */
	public function testConstructionWithInvalidArgs( array $definitionsByType, array $sources ): void {
		$this->expectException( InvalidArgumentException::class );

		new EntitySourceAndTypeDefinitions(
			$definitionsByType,
			$sources
		);
	}

	public function invalidConstructorArgsProvider() {
		yield 'sources param contains non-EntitySource object' => [
			'definitionsByType' => [ DatabaseEntitySource::TYPE => $this->createStub( EntityTypeDefinitions::class ) ],
			'sources' => [ 'i am not an entity source' ],
		];

		yield 'entityTypeDefinitionsBySourceType array contains non-EntityTypeDefinitions object' => [
			'definitionsByType' => [ DatabaseEntitySource::TYPE => 'i am not an entity type def object' ],
			'sources' => [ NewDatabaseEntitySource::create()->build() ],
		];
	}

}
