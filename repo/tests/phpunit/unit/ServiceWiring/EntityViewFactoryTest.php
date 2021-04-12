<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityViewFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [] )
		);

		$this->assertInstanceOf(
			DispatchingEntityViewFactory::class,
			$this->getService( 'WikibaseRepo.EntityViewFactory' )
		);
	}

}
