<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FulltextSearchTypesTest extends ServiceWiringTestCase {

	private function getEntityTypeDefinitions( array $typesToContexts ): EntityTypeDefinitions {
		return new EntityTypeDefinitions( array_map( function ( $context ){
			return [
				EntityTypeDefinitions::FULLTEXT_SEARCH_CONTEXT => $context,
			];
		}, $typesToContexts ) );
	}

	public function testReturnsFullTextSearchContexts(): void {
		$mockFulltextContextStrings = [
			'something' => 'test-context',
			'another' => 'another-context',
		];

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->getEntityTypeDefinitions( $mockFulltextContextStrings )
		);

		$this->assertSame(
			$mockFulltextContextStrings,
			$this->getService( 'WikibaseRepo.FulltextSearchTypes' )
		);
	}

	public function testReturnsCallbackResults(): void {
		$mockFulltextContextStrings = [
			'something' => 'test-context',
			'another' => function () {
				return 'another-context';
			},
		];

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->getEntityTypeDefinitions( $mockFulltextContextStrings )
		);

		$this->assertSame(
			[
				'something' => 'test-context',
				'another' => 'another-context',
			],
			$this->getService( 'WikibaseRepo.FulltextSearchTypes' )
		);
	}

}
