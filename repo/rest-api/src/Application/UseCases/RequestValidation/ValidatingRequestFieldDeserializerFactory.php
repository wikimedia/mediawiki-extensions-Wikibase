<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\Repo\RestApi\Application\Serialization\StatementDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\ItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\Validation\EditMetadataValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementIdValidator;
use Wikibase\Repo\RestApi\Application\Validation\StatementValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\ItemParts;

/**
 * @license GPL-2.0-or-later
 */
class ValidatingRequestFieldDeserializerFactory {

	private LanguageCodeValidator $languageCodeValidator;
	private StatementDeserializer $statementDeserializer;
	private JsonPatchValidator $patchValidator;
	private ItemLabelValidator $itemLabelValidator;
	private int $maxCommentLength;
	private array $allowedTags;

	public function __construct(
		LanguageCodeValidator $languageCodeValidator,
		StatementDeserializer $statementDeserializer,
		JsonPatchValidator $patchValidator,
		ItemLabelValidator $itemLabelValidator,
		int $maxCommentLength,
		array $allowedTags
	) {
		$this->languageCodeValidator = $languageCodeValidator;
		$this->statementDeserializer = $statementDeserializer;
		$this->patchValidator = $patchValidator;
		$this->itemLabelValidator = $itemLabelValidator;
		$this->maxCommentLength = $maxCommentLength;
		$this->allowedTags = $allowedTags;
	}

	public function newItemIdRequestValidatingDeserializer(): ItemIdRequestValidatingDeserializer {
		return new ItemIdRequestValidatingDeserializer( new ItemIdValidator() );
	}

	public function newPropertyIdRequestValidatingDeserializer(): MappedRequestValidatingDeserializer {
		$propertyIdValidatingDeserializer = new PropertyIdValidatingDeserializer( new PropertyIdValidator() );
		return new MappedRequestValidatingDeserializer(
			fn( PropertyIdRequest $r ) => $propertyIdValidatingDeserializer->validateAndDeserialize( $r->getPropertyId() )
		);
	}

	public function newStatementIdRequestValidatingDeserializer(): StatementIdRequestValidatingDeserializer {
		$entityIdParser = new BasicEntityIdParser();

		return new StatementIdRequestValidatingDeserializer(
			new StatementIdValidator( $entityIdParser ),
			new StatementGuidParser( $entityIdParser )
		);
	}

	public function newPropertyIdFilterRequestValidatingDeserializer(): MappedRequestValidatingDeserializer {
		$propertyIdValidatingDeserializer = new PropertyIdValidatingDeserializer( new PropertyIdValidator() );
		return new MappedRequestValidatingDeserializer(
			fn( PropertyIdFilterRequest $r ) => $r->getPropertyIdFilter() === null
				? null
				// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
				: $propertyIdValidatingDeserializer->validateAndDeserialize( $r->getPropertyIdFilter() )
		);
	}

	public function newLanguageCodeRequestValidatingDeserializer(): LanguageCodeRequestValidatingDeserializer {
		return new LanguageCodeRequestValidatingDeserializer( $this->languageCodeValidator );
	}

	public function newItemFieldsRequestValidatingDeserializer(): MappedRequestValidatingDeserializer {
		$fieldsValidator = new FieldsFilterValidatingDeserializer( ItemParts::VALID_FIELDS );
		return new MappedRequestValidatingDeserializer(
			fn( ItemFieldsRequest $r ) => $fieldsValidator->validateAndDeserialize( $r->getItemFields() )
		);
	}

	public function newStatementSerializationRequestValidatingDeserializer(): StatementSerializationRequestValidatingDeserializer {
		return new StatementSerializationRequestValidatingDeserializer(
			new StatementValidator( $this->statementDeserializer )
		);
	}

	public function newEditMetadataRequestValidatingDeserializer(): EditMetadataRequestValidatingDeserializer {
		return new EditMetadataRequestValidatingDeserializer(
			new EditMetadataValidator( $this->maxCommentLength, $this->allowedTags )
		);
	}

	public function newPatchRequestValidatingDeserializer(): PatchRequestValidatingDeserializer {
		return new PatchRequestValidatingDeserializer( $this->patchValidator );
	}

	public function newItemLabelEditRequestValidatingDeserializer(): ItemLabelEditRequestValidatingDeserializer {
		return new ItemLabelEditRequestValidatingDeserializer( $this->itemLabelValidator );
	}

}
