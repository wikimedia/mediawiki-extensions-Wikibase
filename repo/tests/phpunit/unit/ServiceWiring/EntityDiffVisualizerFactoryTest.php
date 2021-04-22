<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityDiffVisualizerFactoryTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService(
			'WikibaseRepo.EntityTypeDefinitions',
			$this->createMock( EntityTypeDefinitions::class )
		);
		$this->mockService(
			'WikibaseRepo.EntityIdHtmlLinkFormatterFactory',
			$this->createMock( EntityIdFormatterFactory::class )
		);
		$this->mockService(
			'WikibaseRepo.SnakFormatterFactory',
			$this->createMock( OutputFormatSnakFormatterFactory::class )
		);

		$this->serviceContainer
			->expects( $this->once() )
			->method( 'getSiteLookup' );
		$this->assertInstanceOf(
			EntityDiffVisualizerFactory::class,
			$this->getService( 'WikibaseRepo.EntityDiffVisualizerFactory' )
		);
	}
}
