<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\DeserializedGetItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class UseCaseValidatorsTest extends TestCase {

	/**
	 * @dataProvider validatorProvider
	 */
	public function testValidators( string $validatorClass, string $requestClass, string $deserializedRequestClass ): void {
		$request = $this->createStub( $requestClass );
		$validatingRequestDeserializer = $this->createMock( ValidatingRequestDeserializer::class );
		$validatingRequestDeserializer->expects( $this->once() )
			->method( 'validateAndDeserialize' )
			->with( $request )
			->willReturn( [] );

		$this->assertInstanceOf(
			$deserializedRequestClass,
			( new $validatorClass( $validatingRequestDeserializer ) )->validateAndDeserialize( $request )
		);
	}

	public function validatorProvider(): Generator {
		yield [
			GetItemLabelsValidator::class,
			GetItemLabelsRequest::class,
			DeserializedGetItemLabelsRequest::class,
		];
	}

}
