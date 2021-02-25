<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValueFormatterFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->serviceContainer->expects( $this->once() )
			->method( 'get' )
			->with( 'WikibaseRepo.DataTypeDefinitions' )
			->willReturn( new DataTypeDefinitions( [] ) );

		$this->assertInstanceOf(
			OutputFormatValueFormatterFactory::class,
			$this->getService( 'WikibaseRepo.ValueFormatterFactory' )
		);
	}

}
