<?php
declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use ValueValidators\ValueValidator;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DataTypeValidatorFactoryTest extends ServiceWiringTestCase {

	public function testConstruction(): void {
		$this->mockService(
			'WikibaseRepo.DataTypeDefinitions',
			new DataTypeDefinitions( [] )
		);

		$this->assertInstanceOf(
			DataTypeValidatorFactory::class,
			$this->getService( 'WikibaseRepo.DataTypeValidatorFactory' )
		);
	}

	public function testReturnsValidatorsFromCallbacks(): void {
		$fakeValidators = [
			$this->createMock( ValueValidator::class ),
		];

		$dataTypeDefs = new DataTypeDefinitions( [
			'PT:some-property' => [
				'value-type' => 'string',
				'validator-factory-callback' => function () use ( $fakeValidators ): array {
					return $fakeValidators;
				},
			],
		] );

		$this->mockService(
			'WikibaseRepo.DataTypeDefinitions',
			$dataTypeDefs
		);

		/** @var DataTypeValidatorFactory $validatorFactory */
		$validatorFactory = $this->getService( 'WikibaseRepo.DataTypeValidatorFactory' );

		$this->assertSame(
			$validatorFactory->getValidators( 'some-property' ),
			$fakeValidators
		);
	}

}
