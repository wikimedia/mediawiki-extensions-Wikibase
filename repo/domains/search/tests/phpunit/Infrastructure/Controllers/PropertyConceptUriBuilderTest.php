<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\Controllers;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceLookup;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Repo\Domains\Search\Infrastructure\Controllers\PropertyConceptUriBuilder;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\Controllers\PropertyConceptUriBuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyConceptUriBuilderTest extends TestCase {

	private const BASE_URI = 'http://www.wikidata.org/entity/';

	public function testLocalProperty(): void {
		$this->assertSame(
			self::BASE_URI . 'P42',
			$this->newBuilder()->buildConceptUri( new NumericPropertyId( 'P42' ) )
		);
	}

	public function testFederatedProperty(): void {
		$this->assertSame(
			self::BASE_URI . 'P42',
			$this->newBuilder()->buildConceptUri( new FederatedPropertyId( self::BASE_URI . 'P42', 'P42' ) )
		);
	}

	private function newBuilder(): PropertyConceptUriBuilder {
		$entitySource = $this->createStub( EntitySource::class );
		$entitySource->method( 'getConceptBaseUri' )->willReturn( self::BASE_URI );

		$entitySourceLookup = $this->createStub( EntitySourceLookup::class );
		$entitySourceLookup->method( 'getEntitySourceById' )->willReturn( $entitySource );

		return new PropertyConceptUriBuilder( $entitySourceLookup );
	}

}
