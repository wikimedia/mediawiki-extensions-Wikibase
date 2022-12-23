<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\EntityIdLabelFormatterFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdLabelFormatterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService( 'WikibaseRepo.FallbackLabelDescriptionLookupFactory',
			$this->createMock( FallbackLabelDescriptionLookupFactory::class ) );

		$this->assertInstanceOf( EntityIdLabelFormatterFactory::class,
			$this->getService( 'WikibaseRepo.EntityIdLabelFormatterFactory' ) );
	}

}
