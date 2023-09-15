<?php declare( strict_types=1 );

namespace Wikibase\Repo\Tests\RestApi\Application\UseCases;

use Generator;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\DeserializedAddItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\DeserializedAddPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\DeserializedGetItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\DeserializedGetItemAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\DeserializedGetItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\GetItemAliasesInLanguageValidator;
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
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\DeserializedGetPropertyLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\GetPropertyLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\DeserializedGetPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\DeserializedGetPropertyStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\DeserializedGetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\DeserializedPatchItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\DeserializedPatchItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\DeserializedPatchPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\DeserializedPatchStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\DeserializedRemoveItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\DeserializedRemoveStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\DeserializedReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\DeserializedReplacePropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\DeserializedReplaceStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementValidator;
use Wikibase\Repo\RestApi\Application\UseCases\RequestValidation\ValidatingRequestDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\DeserializedSetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\DeserializedSetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator;

/**
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItem\GetItemValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\GetItemAliasesInLanguageValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\GetItemDescriptionsValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\GetItemLabelsValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\GetItemStatementsValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\GetPropertyStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetStatement\GetStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\GetItemLabelValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\AddItemStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\PatchItemLabelsValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\RemoveItemStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\RemoveStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\SetItemLabelValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\SetItemDescriptionValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\ReplaceStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\ReplaceItemStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\ReplacePropertyStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\PatchItemStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\PatchPropertyStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\PatchStatementValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\GetPropertyStatementsValidator
 * @covers \Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\AddPropertyStatementValidator
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
			GetItemAliasesValidator::class,
			GetItemAliasesRequest::class,
			DeserializedGetItemAliasesRequest::class,
		];
		yield [
			GetItemAliasesInLanguageValidator::class,
			GetItemAliasesInLanguageRequest::class,
			DeserializedGetItemAliasesInLanguageRequest::class,
		];
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
		yield [
			GetItemValidator::class,
			GetItemRequest::class,
			DeserializedGetItemRequest::class,
		];
		yield [
			AddItemStatementValidator::class,
			AddItemStatementRequest::class,
			DeserializedAddItemStatementRequest::class,
		];
		yield [
			PatchItemLabelsValidator::class,
			PatchItemLabelsRequest::class,
			DeserializedPatchItemLabelsRequest::class,
		];
		yield [
			RemoveItemStatementValidator::class,
			RemoveItemStatementRequest::class,
			DeserializedRemoveItemStatementRequest::class,
		];
		yield [
			RemoveStatementValidator::class,
			RemoveStatementRequest::class,
			DeserializedRemoveStatementRequest::class,
		];
		yield [
			SetItemLabelValidator::class,
			SetItemLabelRequest::class,
			DeserializedSetItemLabelRequest::class,
		];
		yield [
			SetItemDescriptionValidator::class,
			SetItemDescriptionRequest::class,
			DeserializedSetItemDescriptionRequest::class,
		];
		yield [
			ReplaceStatementValidator::class,
			ReplaceStatementRequest::class,
			DeserializedReplaceStatementRequest::class,
		];
		yield [
			ReplaceItemStatementValidator::class,
			ReplaceItemStatementRequest::class,
			DeserializedReplaceItemStatementRequest::class,
		];
		yield [
			ReplacePropertyStatementValidator::class,
			ReplacePropertyStatementRequest::class,
			DeserializedReplacePropertyStatementRequest::class,
		];
		yield [
			GetPropertyLabelsValidator::class,
			GetPropertyLabelsRequest::class,
			DeserializedGetPropertyLabelsRequest::class,
		];
		yield [
			PatchItemStatementValidator::class,
			PatchItemStatementRequest::class,
			DeserializedPatchItemStatementRequest::class,
		];
		yield [
			PatchPropertyStatementValidator::class,
			PatchPropertyStatementRequest::class,
			DeserializedPatchPropertyStatementRequest::class,
		];
		yield [
			PatchStatementValidator::class,
			PatchStatementRequest::class,
			DeserializedPatchStatementRequest::class,
		];
		yield [
			GetPropertyStatementsValidator::class,
			GetPropertyStatementsRequest::class,
			DeserializedGetPropertyStatementsRequest::class,
		];
		yield [
			AddPropertyStatementValidator::class,
			AddPropertyStatementRequest::class,
			DeserializedAddPropertyStatementRequest::class,
		];
	}

}
