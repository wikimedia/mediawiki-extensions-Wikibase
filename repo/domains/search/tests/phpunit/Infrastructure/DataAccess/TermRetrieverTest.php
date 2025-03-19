<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\Domains\Search\Infrastructure\DataAccess;

use MediaWiki\Language\Language;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
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

		$termLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $entityId )
			->willReturn( new TermFallback( $languageCode, $labelText, $languageCode, null ) );

		$this->assertEquals(
			( $this->newTermRetriever( $languageCode, $termLookup ) )->getLabel( $entityId, $languageCode ),
			new Label( $languageCode, $labelText )
		);
	}

	public function testGivenNoLabelInRequestedLanguage_getLabelReturnsNull(): void {
		$this->assertNull(
			( $this->newTermRetriever( 'ko', $this->createStub( FallbackLabelDescriptionLookup::class ) ) )
				->getLabel( new ItemId( 'Q321' ), 'ko' )
		);
	}

	public function testGetDescription(): void {
		$entityId = $this->createStub( EntityId::class );
		$languageCode = 'en';
		$descriptionText = 'some description';

		$termLookup = $this->createMock( FallbackLabelDescriptionLookup::class );
		$termLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $entityId )
			->willReturn( new TermFallback( $languageCode, $descriptionText, $languageCode, null ) );

		$this->assertEquals(
			( $this->newTermRetriever( $languageCode, $termLookup ) )->getDescription( $entityId, $languageCode ),
			new Description( $languageCode, $descriptionText ),
		);
	}

	public function testGivenNoDescriptionInRequestedLanguage_getDescriptionReturnsNull(): void {
		$this->assertNull(
			( $this->newTermRetriever( 'ko', $this->createStub( FallbackLabelDescriptionLookup::class ) ) )
				->getDescription( new ItemId( 'Q321' ), 'ko' )
		);
	}

	private function newTermRetriever( string $languageCode, FallbackLabelDescriptionLookup $labelDescriptionLookup ): TermRetriever {
		$fallbackLookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$fallbackLookupFactory->expects( $this->once() )
			->method( 'newLabelDescriptionLookup' )
			->with( $this->callback( fn( Language $l ) => $l->getCode() === $languageCode ) )
			->willReturn( $labelDescriptionLookup );

		return new TermRetriever( $fallbackLookupFactory, MediaWikiServices::getInstance()->getLanguageFactory() );
	}

}
