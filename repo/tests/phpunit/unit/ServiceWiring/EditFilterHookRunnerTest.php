<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EditFilterHookRunnerTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.EntityTitleStoreLookup',
			$this->createMock( EntityTitleStoreLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityNamespaceLookup',
			$this->createMock( EntityNamespaceLookup::class )
		);

		$this->mockService(
			'WikibaseRepo.EntityContentFactory',
			$this->createMock( EntityContentFactory::class )
		);

		$this->assertInstanceOf(
			EditFilterHookRunner::class,
			$this->getService( 'WikibaseRepo.EditFilterHookRunner' )
		);
	}

}
