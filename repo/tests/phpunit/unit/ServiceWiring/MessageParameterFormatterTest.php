<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Language;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MessageParameterFormatterTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$mockValueFormatterFactory = $this->createMock( OutputFormatValueFormatterFactory::class );
		$mockValueFormatter = $this->createMock( ValueFormatter::class );

		$mockValueFormatterFactory
			->expects( $this->once() )
			->method( 'getValueFormatter' )
			->willReturn( $mockValueFormatter );

		$this->mockService(
			'WikibaseRepo.ValueFormatterFactory',
			$mockValueFormatterFactory
		);

		$this->mockService(
			'WikibaseRepo.EntityTitleLookup',
			$this->createMock( EntityTitleLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.UserLanguage',
			$this->createMock( Language::class )
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getSiteLookup' );

		$this->assertInstanceOf(
			ValueFormatter::class,
			$this->getService( 'WikibaseRepo.MessageParameterFormatter' )
		);
	}

}
