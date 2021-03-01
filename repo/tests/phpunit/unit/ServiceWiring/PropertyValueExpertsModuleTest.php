<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\Modules\PropertyValueExpertsModule;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyValueExpertsModuleTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$testExpertModule = 'foo';
		$this->mockService( 'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( [
				"PT:foobar" => [ 'value-type' => 'string', 'expert-module' => $testExpertModule ],
			] ) );

		/** @var PropertyValueExpertsModule $propertyValueExpertsModule */
		$propertyValueExpertsModule = $this->getService( 'WikibaseRepo.PropertyValueExpertsModule' );

		$this->assertInstanceOf(
			PropertyValueExpertsModule::class,
			$propertyValueExpertsModule
		);
		$this->assertSame( [ $testExpertModule ], $propertyValueExpertsModule->getDependencies() );
	}

}
