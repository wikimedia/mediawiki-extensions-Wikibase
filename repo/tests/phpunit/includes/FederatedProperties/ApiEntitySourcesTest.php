<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Tests\FederatedProperties;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\Tests\NewEntitySource;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\FederatedProperties\ApiEntitySources;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiEntitySources
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ApiEntitySourcesTest extends TestCase {

	public function testGetApiPropertySource(): void {
		$apiPropertySource = NewEntitySource::havingName( 'im an api property source' )
			->withType( EntitySource::TYPE_API )
			->withEntityNamespaceIdsAndSlots( [ 'property' => [ 'namespaceId' => 122, 'slot' => 'main' ] ] )
			->build();
		$apiEntitySources = new ApiEntitySources(
			new EntitySourceDefinitions( [
				NewEntitySource::havingName( 'im a db source' )->build(),
				$apiPropertySource,
			], new SubEntityTypesMapper( [] ) )
		);

		$this->assertSame(
			$apiPropertySource,
			$apiEntitySources->getApiPropertySource()
		);
	}

	/**
	 * @dataProvider entitySourcesWithoutApiPropertySourcesProvider
	 */
	public function testGivenNoApiPropertySource_throws( EntitySource $source ): void {
		$this->expectException( LogicException::class );

		( new ApiEntitySources(
			new EntitySourceDefinitions( [ $source ], new SubEntityTypesMapper( [] ) )
		) )->getApiPropertySource();
	}

	public function entitySourcesWithoutApiPropertySourcesProvider(): Generator {
		yield 'no property source' => [
			NewEntitySource::havingName( 'item api source (technically not yet a thing)' )
				->withType( EntitySource::TYPE_API )
				->withEntityNamespaceIdsAndSlots( [ 'item' => [ 'namespaceId' => 120, 'slot' => 'main' ] ] )
				->build(),
		];

		yield 'no api source' => [
			NewEntitySource::havingName( 'local prop source' )
				->withType( EntitySource::TYPE_DB )
				->withEntityNamespaceIdsAndSlots( [ 'property' => [ 'namespaceId' => 122, 'slot' => 'main' ] ] )
				->build(),
		];
	}

}
