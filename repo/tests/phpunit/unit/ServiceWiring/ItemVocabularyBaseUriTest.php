<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use MediaWiki\Revision\SlotRecord;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemVocabularyBaseUriTest extends ServiceWiringTestCase {

	private function getEntitySourceDefinitions( array $baseUriToEntityTypes ): EntitySourceDefinitions {
		$length = count( $baseUriToEntityTypes );

		return new EntitySourceDefinitions(
			array_map(
				function ( string $baseUri, array $entityTypes, int $idx ): DatabaseEntitySource {
					return new DatabaseEntitySource(
						'test' . $idx,
						false,
						array_fill_keys(
							$entityTypes,
							[ 'namespaceId' => $idx, 'slot' => SlotRecord::MAIN ]
						),
						$baseUri,
						'',
						'',
						''
					);
				},
				array_keys( $baseUriToEntityTypes ),
				$baseUriToEntityTypes,
				$length > 0 ? range( 1, $length ) : []
			),
			new SubEntityTypesMapper( [] )
		);
	}

	public function testReturnsEntitySourceBaseUri(): void {
		$baseUri = 'test.test/items';

		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			$this->getEntitySourceDefinitions( [
				'test.source/somethings' => [ 'something' ],
				$baseUri => [ Item::ENTITY_TYPE ],
			] )
		);

		$this->assertSame(
			$baseUri,
			$this->getService( 'WikibaseRepo.ItemVocabularyBaseUri' )
		);
	}

	public function testThrowsWhenNoItemSourceDefined(): void {
		$this->mockService(
			'WikibaseRepo.EntitySourceDefinitions',
			$this->getEntitySourceDefinitions( [
				'test.source/somethings' => [ 'something' ],
			] )
		);

		$this->expectException( 'LogicException' );

		$this->getService( 'WikibaseRepo.ItemVocabularyBaseUri' );
	}
}
