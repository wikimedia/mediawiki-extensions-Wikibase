<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Lookup\TermLookupException;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Repo\RestApi\Domain\ReadModel\Description;
use Wikibase\Repo\RestApi\Domain\ReadModel\Descriptions;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Domain\ReadModel\Labels;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\TermLookupEntityTermsRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\TermLookupEntityTermsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermLookupEntityTermsRetrieverTest extends TestCase {

	private const ALL_TERM_LANGUAGES = [ 'de', 'en', 'ko' ];
	private const ITEM_ID = 'Q123';
	private const PROPERTY_ID = 'P123';

	public function testGetItemLabels(): void {
		$itemId = new ItemId( self::ITEM_ID );

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getLabels' )
			->with( $itemId, self::ALL_TERM_LANGUAGES )
			->willReturn( [
				'en' => 'potato',
				'de' => 'Kartoffel',
				'ko' => '감자',
			] );

		$this->assertEquals(
			( $this->newTermRetriever( $termLookup ) )->getLabels( $itemId ),
			new Labels(
				new Label( 'en', 'potato' ),
				new Label( 'de', 'Kartoffel' ),
				new Label( 'ko', '감자' ),
			)
		);
	}

	public function testGetPropertyLabels(): void {
		$propertyId = new NumericPropertyId( self::PROPERTY_ID );

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getLabels' )
			->with( $propertyId, self::ALL_TERM_LANGUAGES )
			->willReturn( [
				'en' => 'weight',
				'de' => 'Gewicht',
				'ko' => '무게',
			] );

		$this->assertEquals(
			( $this->newTermRetriever( $termLookup ) )->getLabels( $propertyId ),
			new Labels(
				new Label( 'en', 'weight' ),
				new Label( 'de', 'Gewicht' ),
				new Label( 'ko', '무게' ),
			)
		);
	}

	public function testLabelsLookupThrowsLookupException_returnsNull(): void {
		$entityId = $this->createStub( EntityId::class );

		$termLookup = $this->createStub( TermLookup::class );
		$termLookup->method( 'getLabels' )
			->willThrowException( new TermLookupException( $entityId, [] ) );

		$this->assertNull( $this->newTermRetriever( $termLookup )->getLabels( $entityId ) );
	}

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

	public function testGetItemDescriptions(): void {
		$itemId = new ItemId( self::ITEM_ID );

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getDescriptions' )
			->with( $itemId, self::ALL_TERM_LANGUAGES )
			->willReturn( [
				'en' => 'English science fiction writer and humourist',
				'de' => 'britischer Science-Fiction-Autor und Humorist (1952–2001)',
				'ko' => '영국의 작가',
			] );

		$this->assertEquals(
			( $this->newTermRetriever( $termLookup ) )->getDescriptions( $itemId ),
			new Descriptions(
				new Description( 'en', 'English science fiction writer and humourist' ),
				new Description( 'de', 'britischer Science-Fiction-Autor und Humorist (1952–2001)' ),
				new Description( 'ko', '영국의 작가' ),
			)
		);
	}

	public function testGetPropertyDescriptions(): void {
		$propertyId = new NumericPropertyId( self::PROPERTY_ID );

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getDescriptions' )
			->with( $propertyId, self::ALL_TERM_LANGUAGES )
			->willReturn( [
				'en' => 'english test property',
				'de' => 'deutsche Test-Eigenschaft',
				'ko' => '한국어 시험 속성',
			] );

		$this->assertEquals(
			( $this->newTermRetriever( $termLookup ) )->getDescriptions( $propertyId ),
			new Descriptions(
				new Description( 'en', 'english test property' ),
				new Description( 'de', 'deutsche Test-Eigenschaft' ),
				new Description( 'ko', '한국어 시험 속성' ),
			)
		);
	}

	public function testDescriptionsLookupThrowsLookupException_returnsNull(): void {
		$entityId = $this->createStub( EntityId::class );

		$termLookup = $this->createStub( TermLookup::class );
		$termLookup->method( 'getDescriptions' )
			->willThrowException( new TermLookupException( $entityId, [] ) );

		$this->assertNull( $this->newTermRetriever( $termLookup )->getDescriptions( $entityId ) );
	}

	private function newTermRetriever( TermLookup $termLookup ): TermLookupEntityTermsRetriever {
		return new TermLookupEntityTermsRetriever(
			$termLookup,
			new StaticContentLanguages( self::ALL_TERM_LANGUAGES )
		);
	}

	public function testGetDescription(): void {
		$itemId = new ItemId( self::ITEM_ID );

		$termLookup = $this->createMock( TermLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $itemId, 'en' )
			->willReturn( 'English science fiction writer and humourist' );

		$this->assertEquals(
			( $this->newTermRetriever( $termLookup ) )->getDescription( $itemId, 'en' ),
			new Description( 'en', 'English science fiction writer and humourist' ),
		);
	}

	public function testGivenNoDescriptionInRequestedLanguage_getDescriptionReturnsNull(): void {
		$this->assertNull(
			( $this->newTermRetriever( $this->createStub( TermLookup::class ) ) )
				->getDescription( new ItemId( 'Q321' ), 'ko' )
		);
	}
}
