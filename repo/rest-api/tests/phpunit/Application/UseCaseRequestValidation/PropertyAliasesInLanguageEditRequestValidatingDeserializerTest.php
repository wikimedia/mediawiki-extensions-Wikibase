<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCaseRequestValidation;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyAliasesInLanguageEditRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyAliasesInLanguageEditRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\RestApi\Domain\ReadModel\AliasesInLanguage;
use Wikibase\Repo\RestApi\Domain\Services\PropertyAliasesInLanguageRetriever;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\PropertyAliasesInLanguageEditRequestValidatingDeserializer
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyAliasesInLanguageEditRequestValidatingDeserializerTest extends TestCase {

	/**
	 * @dataProvider provideValidAliases
	 */
	public function testGivenValidRequest_returnsAliases( array $aliases, array $expectedDeserializedAliases ): void {
		$request = $this->createStub( PropertyAliasesInLanguageEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getAliasesInLanguage' )->willReturn( $aliases );

		$requestValidatingDeserializer = new PropertyAliasesInLanguageEditRequestValidatingDeserializer(
			new AliasesDeserializer(),
			$this->createStub( AliasesInLanguageValidator::class ),
			$this->newStubPropertyAliasesInLanguageRetriever()
		);

		$this->assertSame( $expectedDeserializedAliases, $requestValidatingDeserializer->validateAndDeserialize( $request ) );
	}

	public function provideValidAliases(): Generator {
		yield 'valid aliases pass validation' => [
			[ 'first alias', 'second alias' ],
			[ 'first alias', 'second alias' ],
		];

		yield 'white space is trimmed from aliases' => [
			[ ' space at the start', "\ttab at the start", 'space at end ', "tab at end\t", "\t  multiple spaces and tabs \t" ],
			[ 'space at the start', 'tab at the start', 'space at end', 'tab at end', 'multiple spaces and tabs' ],
		];
	}

	/**
	 * @dataProvider invalidAliasesProvider
	 */
	public function testWithInvalidAliases(
		UseCaseError $expectedException,
		array $aliases,
		ValidationError $validationError = null,
		array $existingAliases = []
	): void {
		$request = $this->createStub( PropertyAliasesInLanguageEditRequest::class );
		$request->method( 'getPropertyId' )->willReturn( 'P123' );
		$request->method( 'getLanguageCode' )->willReturn( 'en' );
		$request->method( 'getAliasesInLanguage' )->willReturn( $aliases );

		$aliasesValidator = $this->createStub( AliasesInLanguageValidator::class );
		$aliasesValidator->method( 'validate' )->willReturn( $validationError );

		try {
			( new PropertyAliasesInLanguageEditRequestValidatingDeserializer(
				new AliasesDeserializer(),
				$aliasesValidator,
				$this->newStubPropertyAliasesInLanguageRetriever( $existingAliases )
			) )
				->validateAndDeserialize( $request );
			$this->fail( 'this should not be reached' );
		} catch ( UseCaseError $error ) {
			$this->assertEquals( $expectedException, $error );
		}
	}

	public static function invalidAliasesProvider(): Generator {
		yield 'alias list is associative array' => [
			UseCaseError::newInvalidValue( '/aliases' ),
			[ 'not' => 'a', 'sequential' => 'array' ],
		];

		yield 'alias list is empty' => [
			UseCaseError::newInvalidValue( '/aliases' ),
			[],
		];

		yield 'alias at position 0 is not a string' => [
			UseCaseError::newInvalidValue( '/aliases/0' ),
			[ 5675 ],
		];

		yield 'alias at position 1 is not a string' => [
			UseCaseError::newInvalidValue( '/aliases/1' ),
			[ 'alias', 1085 ],
		];

		yield 'alias at position 0 is empty' => [
			UseCaseError::newInvalidValue( '/aliases/0' ),
			[ '' ],
		];

		yield 'alias at position 1 is empty' => [
			UseCaseError::newInvalidValue( '/aliases/1' ),
			[ 'aka', '' ],
		];

		$alias = 'alias that is too long...';
		$limit = 40;
		yield 'alias too long' => [
			UseCaseError::newValueTooLong( '/aliases/0', $limit ),
			[ $alias ],
			new ValidationError(
				AliasesInLanguageValidator::CODE_TOO_LONG,
				[
					AliasesInLanguageValidator::CONTEXT_VALUE => $alias,
					AliasesInLanguageValidator::CONTEXT_LIMIT => $limit,
				]
			),
		];

		$invalidAlias = "tab characters \t not allowed";
		yield 'alias invalid' => [
			UseCaseError::newInvalidValue( '/aliases/0' ),
			[ $invalidAlias ],
			new ValidationError(
				AliasesInLanguageValidator::CODE_INVALID,
				[ AliasesInLanguageValidator::CONTEXT_VALUE => $invalidAlias ]
			),
		];

		$duplicateAlias = 'foo';
		yield 'alias duplicate in the request' => [
			new UseCaseError(
				UseCaseError::ALIAS_DUPLICATE,
				"Alias list contains a duplicate alias: '$duplicateAlias'",
				[ UseCaseError::CONTEXT_ALIAS => $duplicateAlias ]
			),
			[ $duplicateAlias, 'bar', $duplicateAlias ],
		];

		$duplicateAlias = 'foo';
		yield 'alias already exists' => [
			new UseCaseError(
				UseCaseError::ALIAS_DUPLICATE,
				"Alias list contains a duplicate alias: '$duplicateAlias'",
				[ UseCaseError::CONTEXT_ALIAS => $duplicateAlias ]
			),
			[ $duplicateAlias, 'bar' ],
			null,
			[ $duplicateAlias, 'baz' ],
		];
	}

	private function newStubPropertyAliasesInLanguageRetriever( array $enAliasesToReturn = [] ): PropertyAliasesInLanguageRetriever {
		$retriever = $this->createStub( PropertyAliasesInLanguageRetriever::class );
		$retriever->method( 'getAliasesInLanguage' )->willReturn( new AliasesInLanguage( 'en', $enAliasesToReturn ) );

		return $retriever;
	}

}
