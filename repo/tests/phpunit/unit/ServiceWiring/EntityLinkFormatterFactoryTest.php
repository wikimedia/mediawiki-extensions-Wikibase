<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Language;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatter;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityLinkFormatterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityTitleTextLookup',
			$this->createMock( EntityTitleTextLookup::class )
		);

		$this->serviceContainer->expects( $this->once() )
			->method( 'getLanguageFactory' );

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] )
		);

		$this->assertInstanceOf(
			EntityLinkFormatterFactory::class,
			$this->getService( 'WikibaseRepo.EntityLinkFormatterFactory' )
		);
	}

	public function testConstructionWithCallback(): void {
		$mockLanguage = $this->createMock( Language::class );
		$mockFormatter = $this->createMock( EntityLinkFormatter::class );

		$callback = function ( Language $language ) use ( $mockLanguage, $mockFormatter ): EntityLinkFormatter {
			$this->assertSame( $language, $mockLanguage );

			return $mockFormatter;
		};

		$this->mockService(
			'WikibaseRepo.EntityTitleTextLookup',
			$this->createMock( EntityTitleTextLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'something' => [
					EntityTypeDefinitions::LINK_FORMATTER_CALLBACK => $callback,
				],
			] )
		);

		/** @var EntityLinkFormatterFactory $formatterFactory */
		$formatterFactory = $this->getService( 'WikibaseRepo.EntityLinkFormatterFactory' );

		$this->assertSame(
			$mockFormatter,
			$formatterFactory->getLinkFormatter( 'something', $mockLanguage )
		);
	}

}
