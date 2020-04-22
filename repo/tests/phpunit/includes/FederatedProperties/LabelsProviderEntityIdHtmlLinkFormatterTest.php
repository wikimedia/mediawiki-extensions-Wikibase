<?php

namespace Wikibase\Repo\Tests\FederatedProperties;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermFallback;
use Wikibase\Lib\LanguageFallbackIndicator;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Repo\FederatedProperties\LabelsProviderEntityIdHtmlLinkFormatter;

/**
 * @covers \Wikibase\Repo\FederatedProperties\ApiPropertyDataTypeLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class LabelsProviderEntityIdHtmlLinkFormatterTest extends TestCase {

	public function provide() {
		return [
			'With label' =>
			[ 'P123', new Term( 'en', 'baz' ), 'someUrl', '', '<a href="someUrl">baz</a>' ],
			'With fallback label' =>
				[ 'P123', new TermFallback( 'en', 'baz', 'de', 'fr' ), 'someUrl', '[fall]', '<a href="someUrl" lang="de">baz</a>[fall]' ],
			'Ensure fallback isn\'t used for just a Term' =>
			[ 'P345', new Term( 'en', 'bar' ), 'aUrl', '[fall]', '<a href="aUrl">bar</a>' ],
			'With no label' =>
			[ 'P345', null, 'aUrl', '[fall]', '<a href="aUrl">P345</a>' ],
		];
	}

	/**
	 * @dataProvider provide
	 */
	public function test( $propertyString, $labelTerm, $url, $fallbackHtml, $expected ) {
		$entityId = new PropertyId( $propertyString );

		$mockLabelDescriptionLookup = $this->createMock( LabelDescriptionLookup::class );
		$mockLabelDescriptionLookup->expects( $this->once() )
			->method( 'getLabel' )
			->willReturn( $labelTerm );
		$mockEntityUrlLookup = $this->createMock( EntityUrlLookup::class );
		$mockEntityUrlLookup->expects( $this->once() )
			->method( 'getFullUrl' )
			->willReturn( $url );
		$mockLanguageFallbackIndicator = $this->createMock( LanguageFallbackIndicator::class );
		$mockLanguageFallbackIndicator->expects( $this->any() )
			->method( 'getHtml' )
			->willReturn( $fallbackHtml );

		$service = new LabelsProviderEntityIdHtmlLinkFormatter(
			$mockLabelDescriptionLookup,
			$mockEntityUrlLookup,
			$mockLanguageFallbackIndicator
		);

		$result = $service->formatEntityId( $entityId );

		$this->assertEquals( $expected, $result );
	}

}
