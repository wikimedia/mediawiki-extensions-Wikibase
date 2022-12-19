<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EnabledEntityTypesTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$entityTypeDefinitions = new EntityTypeDefinitions( [
			'lexeme' => [
				EntityTypeDefinitions::SUB_ENTITY_TYPES => [
					'form',
				],
			],
		] );
		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( [
				new DatabaseEntitySource(
					'local',
					false,
					[
						'foo' => [ 'namespaceId' => 200, 'slot' => SlotRecord::MAIN ],
						'bar' => [ 'namespaceId' => 220, 'slot' => SlotRecord::MAIN ],
					],
					'',
					'',
					'',
					''
				),
				new DatabaseEntitySource(
					'bazwiki',
					'bazdb',
					[
						'baz' => [ 'namespaceId' => 250, 'slot' => SlotRecord::MAIN ],
					],
					'',
					'baz',
					'baz',
					'bazwiki'
				),
				new DatabaseEntitySource(
					'lexemewiki',
					'bazdb',
					[
						'lexeme' => [ 'namespaceId' => 280, 'slot' => SlotRecord::MAIN ],
					],
					'',
					'lex',
					'lex',
					'lexwiki'
				),
			], new SubEntityTypesMapper( $entityTypeDefinitions->get( EntityTypeDefinitions::SUB_ENTITY_TYPES ) ) ) );
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			$entityTypeDefinitions );

		/** @var string[] $enabled */
		$enabled = $this->getService( 'WikibaseRepo.EnabledEntityTypes' );

		$this->assertIsArray( $enabled );
		$this->assertContains( 'foo', $enabled );
		$this->assertContains( 'bar', $enabled );
		$this->assertContains( 'baz', $enabled );
		$this->assertContains( 'lexeme', $enabled );
		$this->assertContains( 'form', $enabled );
	}

}
