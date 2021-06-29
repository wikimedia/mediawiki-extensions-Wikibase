<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
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
			new EntityTypeDefinitions( [
				'property' => [
					EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK => $callback1,
				]
			] ),
			new EntityTypeDefinitions( [
				'property' => [
					EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK => $callback2,
				]
			] ),
			[
				new EntitySource(
					'local',
					false,
					[],
					'',
					'',
					'',
					''
				),
				new EntitySource(
					'wikidorta',
					false,
					[],
					'',
					'',
					'',
					'',
					EntitySource::TYPE_API
				)
			]
		);

		$articleIdLookups = $definitions->getServiceBySourceAndType( EntityTypeDefinitions::ARTICLE_ID_LOOKUP_CALLBACK );
		$this->assertEquals(
			[
				'local' => [
					'property' => $callback1
				],
				'wikidorta' => [
					'property' => $callback2
				]
			],
			$articleIdLookups
		);
	}

	public function testGivenUnknownSourceType_throwsLogicException(): void {
		$sourceWithUndefinedType = $this->createMock( EntitySource::class );
		$sourceWithUndefinedType->method( 'getType' )->willReturn( 'blergh' );

		$definitions = new EntitySourceAndTypeDefinitions(
			$this->createStub( EntityTypeDefinitions::class ),
			$this->createStub( EntityTypeDefinitions::class ),
			[ $sourceWithUndefinedType ]
		);

		$this->expectException( LogicException::class );
		$definitions->getServiceBySourceAndType( 'some service' );
	}

}
