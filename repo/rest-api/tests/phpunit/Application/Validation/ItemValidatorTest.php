<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\Validation;

use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Tests\NewItem;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\SerializationException;
use Wikibase\Repo\RestApi\Application\Serialization\UnexpectedFieldException;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;

/**
 * @covers \Wikibase\Repo\RestApi\Application\Validation\ItemValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemValidatorTest extends TestCase {

	private ItemDeserializer $deserializer;

	protected function setUp(): void {
		parent::setUp();
		$this->deserializer = $this->createStub( ItemDeserializer::class );
	}

	/**
	 * @dataProvider deserializationErrorProvider
	 */
	public function testGivenInvalidItemSerialization_validateReturnsValidationError(
		SerializationException $exception,
		string $expectedErrorCode,
		array $expectedContext
	): void {
		$this->deserializer->method( 'deserialize' )->willThrowException( $exception );

		$error = $this->newValidator()->validate( [ 'invalid' => 'serialization' ] );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( $expectedErrorCode, $error->getCode() );
		$this->assertSame( $expectedContext, $error->getContext() );
	}

	public static function deserializationErrorProvider(): Generator {
		yield 'invalid field exception' => [
			new InvalidFieldException( 'some-field', 'some-value' ),
			ItemValidator::CODE_INVALID_FIELD,
			[ 'field' => 'some-field', 'value' => 'some-value' ],
		];

		yield 'unexpected field exception' => [
			new UnexpectedFieldException( 'foo' ),
			ItemValidator::CODE_UNEXPECTED_FIELD,
			[ 'field' => 'foo' ],
		];
	}

	public function testGivenEmptyItem_validateReturnsValidationError(): void {
		$this->deserializer = $this->createStub( ItemDeserializer::class );
		$this->deserializer->method( 'deserialize' )->willReturn( new Item() );

		$error = $this->newValidator()->validate( [] );

		$this->assertInstanceOf( ValidationError::class, $error );
		$this->assertSame( ItemValidator::CODE_MISSING_LABELS_AND_DESCRIPTIONS, $error->getCode() );
	}

	public function testGetValidatedItem_calledAfterValidate(): void {
		$serialization = [ 'labels' => [ 'en' => 'english label' ] ];
		$deserializedItem = NewItem::withLabel( 'en', 'english label' )->build();
		$this->deserializer = $this->createMock( ItemDeserializer::class );
		$this->deserializer->method( 'deserialize' )->with( $serialization )->willReturn( $deserializedItem );

		$validator = $this->newValidator();
		$this->assertNull( $validator->validate( $serialization ) );
		$this->assertSame( $deserializedItem, $validator->getValidatedItem() );
	}

	public function testGetValidatedItem_calledBeforeValidate(): void {
		$this->expectException( LogicException::class );

		$this->newValidator()->getValidatedItem();
	}

	private function newValidator(): ItemValidator {
		return new ItemValidator( $this->deserializer );
	}

}
