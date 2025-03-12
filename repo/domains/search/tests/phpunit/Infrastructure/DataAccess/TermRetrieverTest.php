<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\Repo\Domains\Search\Domain\Model\Description;
use Wikibase\Repo\Domains\Search\Domain\Model\Label;
use Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\TermRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Search\Infrastructure\DataAccess\TermRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermRetrieverTest extends TestCase {

	public function testGetLabel(): void {
		$entityId = $this->createStub( EntityId::class );
		$languageCode = 'en';
		$labelText = 'some label';

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $entityId, $languageCode )
			->willReturn( $labelText );

		$this->assertEquals(
			( $this->newTermRetriever( $termLookup ) )->getLabel( $entityId, $languageCode ),
			new Label( $languageCode, $labelText )
		);
	}

	public function testGivenNoLabelInRequestedLanguage_getLabelReturnsNull(): void {
		$this->assertNull(
			( $this->newTermRetriever( $this->createStub( TermLookup::class ) ) )
				->getLabel( new ItemId( 'Q321' ), 'ko' )
		);
	}

	public function testGetDescription(): void {
		$entityId = $this->createMock( EntityId::class );
		$languageCode = 'en';
		$descriptionText = 'some description';

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $entityId, $languageCode )
			->willReturn( $descriptionText );

		$this->assertEquals(
			( $this->newTermRetriever( $termLookup ) )->getDescription( $entityId, $languageCode ),
			new Description( $languageCode, $descriptionText ),
		);
	}

	public function testGivenNoDescriptionInRequestedLanguage_getDescriptionReturnsNull(): void {
		$this->assertNull(
			( $this->newTermRetriever( $this->createStub( TermLookup::class ) ) )
				->getDescription( new ItemId( 'Q321' ), 'ko' )
		);
	}

	private function newTermRetriever( TermLookup $termLookup ): TermRetriever {
		return new TermRetriever( $termLookup );
	}

}
