<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Crud\Infrastructure\DataAccess;

use Generator;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Description;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Label;
use Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\FallbackLookupFactoryTermsRetriever;

/**
 * @covers \Wikibase\Repo\Domains\Crud\Infrastructure\DataAccess\FallbackLookupFactoryTermsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FallbackLookupFactoryTermsRetrieverTest extends TestCase {

	/**
	 * @dataProvider entityIdGenerator
	 */
	public function testLabelsLookupThrowsLookupException_returnsNull( EntityId $entityId ): void {
		$languageCode = 'en';

		$lookup = $this->createStub( FallbackLabelDescriptionLookup::class );
		$lookup->method( 'getLabel' )
			->willThrowException( new LabelDescriptionLookupException( $entityId, [] ) );

		$this->assertNull( $this->newTermsRetriever( $lookup )->getLabel( $entityId, $languageCode ) );
	}

	/**
	 * @dataProvider entityIdGenerator
	 */
	public function testGetLabel( EntityId $entityId ): void {
		$languageCode = 'en';
		$labelText = 'some label';

		$labelFallback = new TermFallback( $languageCode, $labelText, $languageCode, null );
		$lookup = $this->createStub( FallbackLabelDescriptionLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $entityId )
			->willReturn( $labelFallback );

		$this->assertEquals(
			new Label( $languageCode, $labelText ),
			( $this->newTermsRetriever( $lookup ) )->getLabel( $entityId, $languageCode )
		);
	}

	public function testGetLabelWithFallbackLanguage(): void {
		$entityId = $this->createStub( EntityId::class );
		$languageCode = 'en';
		$fallbackLanguageCode = 'mul';
		$labelText = 'some label';

		$labelFallback = new TermFallback( $languageCode, $labelText, $fallbackLanguageCode, null );
		$lookup = $this->createStub( FallbackLabelDescriptionLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $entityId )
			->willReturn( $labelFallback );

		$this->assertEquals(
			new Label( $fallbackLanguageCode, $labelText ),
			( $this->newTermsRetriever( $lookup ) )->getLabel( $entityId, $languageCode )
		);
	}

	/**
	 * @dataProvider entityIdGenerator
	 */
	public function testGivenNoLabelInRequestedLanguage_getLabelReturnsNull( EntityId $entityId ): void {
		$this->assertNull(
			( $this->newTermsRetriever( $this->createStub( FallbackLabelDescriptionLookup::class ) ) )
				->getLabel( $entityId, 'ko' )
		);
	}

	/**
	 * @dataProvider entityIdGenerator
	 */
	public function testDescriptionLookupThrowsLookupException_returnsNull( EntityId $entityId ): void {
		$lookup = $this->createStub( FallbackLabelDescriptionLookup::class );
		$lookup->method( 'getDescription' )
			->willThrowException( new LabelDescriptionLookupException( $entityId, [] ) );

		$this->assertNull( $this->newTermsRetriever( $lookup )->getDescription( $entityId, 'en' ) );
	}

	/**
	 * @dataProvider entityIdGenerator
	 */
	public function testGetDescription( EntityId $entityId ): void {
		$languageCode = 'en';
		$descriptionText = 'some description';

		$descriptionFallback = new TermFallback( $languageCode, $descriptionText, $languageCode, null );
		$lookup = $this->createStub( FallbackLabelDescriptionLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $entityId )
			->willReturn( $descriptionFallback );

		$this->assertEquals(
			new Description( $languageCode, $descriptionText ),
			( $this->newTermsRetriever( $lookup ) )->getDescription( $entityId, $languageCode )
		);
	}

	/**
	 * @dataProvider entityIdGenerator
	 */
	public function testGetDescriptionWithFallbackLanguage( EntityId $entityId ): void {
		$languageCode = 'en';
		$fallbackLanguageCode = 'mul';
		$descriptionText = 'some description';

		$descriptionFallback = new TermFallback( $languageCode, $descriptionText, $fallbackLanguageCode, null );
		$lookup = $this->createStub( FallbackLabelDescriptionLookup::class );
		$lookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $entityId )
			->willReturn( $descriptionFallback );

		$this->assertEquals(
			new Description( $fallbackLanguageCode, $descriptionText ),
			( $this->newTermsRetriever( $lookup ) )->getDescription( $entityId, $languageCode )
		);
	}

	/**
	 * @dataProvider entityIdGenerator
	 */
	public function testGivenNoDescriptionInRequestedLanguage_getDescriptionReturnsNull( EntityId $entityId ): void {
		$this->assertNull(
			( $this->newTermsRetriever( $this->createStub( FallbackLabelDescriptionLookup::class ) ) )
				->getDescription( $entityId, 'ko' )
		);
	}

	public static function entityIdGenerator(): Generator {
		yield 'item_id' => [ new ItemId( 'Q321' ) ];
		yield 'property_id' => [ new NumericPropertyId( 'P123' ) ];
	}

	private function newTermsRetriever( FallbackLabelDescriptionLookup $lookup ): FallbackLookupFactoryTermsRetriever {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();

		$lookupFactory = $this->createStub( FallbackLabelDescriptionLookupFactory::class );
		$lookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->willReturn( $lookup );

		return new FallbackLookupFactoryTermsRetriever( $languageFactory, $lookupFactory );
	}
}
