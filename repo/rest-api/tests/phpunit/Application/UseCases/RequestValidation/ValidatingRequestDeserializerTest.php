<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases\RequestValidation;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\RestApi\Application\UseCases\ItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ItemIdRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestFieldDeserializerFactory;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseRequest;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ValidatingRequestDeserializerTest extends TestCase {

	public function testGivenValidItemIdRequest_returnsDeserializedItemId(): void {
		// We're using an anonymous class here because PHPUnit 9 does not support stubbing multiple interfaces
		$request = new class implements UseCaseRequest, ItemIdRequest {
			public function getItemId(): string {
				return 'Q123';
			}
		};

		$this->assertEquals(
			[ ItemIdRequestValidatingDeserializer::DESERIALIZED_VALUE => new ItemId( 'Q123' ) ],
			$this->newRequestDeserializer()->validateAndDeserialize( $request )
		);
	}

	public function testGivenInvalidItemIdRequest_throws(): void {
		$expectedError = $this->createStub( UseCaseError::class );
		$itemIdValidator = $this->createStub( ItemIdRequestValidatingDeserializer::class );
		$itemIdValidator->method( 'validateAndDeserialize' )->willThrowException( $expectedError );
		$factory = $this->createStub( ValidatingRequestFieldDeserializerFactory::class );
		$factory->method( 'newItemIdRequestValidatingDeserializer' )->willReturn( $itemIdValidator );

		try {
			$this->newRequestDeserializer( $factory )->validateAndDeserialize(
				new class implements UseCaseRequest, ItemIdRequest {
					public function getItemId(): string {
						return 'P123';
					}
				}
			);
			$this->fail( 'expected exception was not thrown' );
		} catch ( UseCaseError $e ) {
			$this->assertSame( $expectedError, $e );
		}
	}

	// property id
	// statement id
	// statement
	// property id filter
	// requested fields
	// edit metadata

	private function newRequestDeserializer( ValidatingRequestFieldDeserializerFactory $factory = null ): ValidatingRequestDeserializer {
		$factory ??= new ValidatingRequestFieldDeserializerFactory();
		return new ValidatingRequestDeserializer( $factory );
	}

}
