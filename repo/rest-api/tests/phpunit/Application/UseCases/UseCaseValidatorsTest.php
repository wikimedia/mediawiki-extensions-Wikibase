<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\DeserializedGetItemDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\DeserializedGetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\DeserializedGetItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\DeserializedGetItemStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\DeserializedGetPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\DeserializedGetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelValidator
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
			GetItemDescriptionsValidator::class,
			GetItemDescriptionsRequest::class,
			DeserializedGetItemDescriptionsRequest::class,
		];
		yield [
			GetItemLabelsValidator::class,
			GetItemLabelsRequest::class,
			DeserializedGetItemLabelsRequest::class,
		];
		yield [
			GetItemStatementsValidator::class,
			GetItemStatementsRequest::class,
			DeserializedGetItemStatementsRequest::class,
		];
		yield [
			GetPropertyStatementValidator::class,
			GetPropertyStatementRequest::class,
			DeserializedGetPropertyStatementRequest::class,
		];
		yield [
			GetStatementValidator::class,
			GetStatementRequest::class,
			DeserializedGetStatementRequest::class,
		];
		yield [
			GetItemLabelValidator::class,
			GetItemLabelRequest::class,
			DeserializedGetItemLabelRequest::class,
		];
	}

}
