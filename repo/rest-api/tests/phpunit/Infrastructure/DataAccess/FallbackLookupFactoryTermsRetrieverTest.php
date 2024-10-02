<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Infrastructure\DataAccess;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookupException;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\RestApi\Domain\ReadModel\Label;
use Wikibase\Repo\RestApi\Infrastructure\DataAccess\FallbackLookupFactoryTermsRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Infrastructure\DataAccess\FallbackLookupFactoryTermsRetriever
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FallbackLookupFactoryTermsRetrieverTest extends TestCase {

	public function testLabelsLookupThrowsLookupException_returnsNull(): void {
		$entityId = $this->createStub( EntityId::class );
		$languageCode = 'en';

		$lookup = $this->createStub( FallbackLabelDescriptionLookup::class );
		$lookup->method( 'getLabel' )
			->willThrowException( new LabelDescriptionLookupException( $entityId, [] ) );

		$this->assertNull( $this->newTermsRetriever( $lookup )->getLabel( $entityId, $languageCode ) );
	}

	public function testGetLabel(): void {
		$entityId = $this->createStub( EntityId::class );
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

	public function testGivenNoLabelInRequestedLanguage_getLabelReturnsNull(): void {
		$this->assertNull(
			( $this->newTermsRetriever( $this->createStub( FallbackLabelDescriptionLookup::class ) ) )
				->getLabel( new ItemId( 'Q321' ), 'ko' )
		);
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
