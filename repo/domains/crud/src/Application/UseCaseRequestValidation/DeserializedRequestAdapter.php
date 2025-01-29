<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemAliasesInLanguage\DeserializedAddItemAliasesInLanguageRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemStatement\DeserializedAddItemStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyAliasesInLanguage\DeserializedAddPropertyAliasesInLanguageRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement\DeserializedAddPropertyStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\CreateItem\DeserializedCreateItemRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem\DeserializedGetItemRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliases\DeserializedGetItemAliasesRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliasesInLanguage\DeserializedGetItemAliasesInLanguageRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescription\DeserializedGetItemDescriptionRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptions\DeserializedGetItemDescriptionsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback\DeserializedGetItemDescriptionWithFallbackRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabel\DeserializedGetItemLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabels\DeserializedGetItemLabelsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabelWithFallback\DeserializedGetItemLabelWithFallbackRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement\DeserializedGetItemStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements\DeserializedGetItemStatementsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetProperty\DeserializedGetPropertyRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases\DeserializedGetPropertyAliasesRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliasesInLanguage\DeserializedGetPropertyAliasesInLanguageRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescription\DeserializedGetPropertyDescriptionRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions\DeserializedGetPropertyDescriptionsRequest;
// phpcs:ignore Generic.Files.LineLength.TooLong
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback\DeserializedGetPropertyDescriptionWithFallbackRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabel\DeserializedGetPropertyLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabels\DeserializedGetPropertyLabelsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyLabelWithFallback\DeserializedGetPropertyLabelWithFallbackRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatement\DeserializedGetPropertyStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatements\DeserializedGetPropertyStatementsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink\DeserializedGetSitelinkRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelinks\DeserializedGetSitelinksRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem\DeserializedPatchItemRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemAliases\DeserializedPatchItemAliasesRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions\DeserializedPatchItemDescriptionsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels\DeserializedPatchItemLabelsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement\DeserializedPatchItemStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty\DeserializedPatchPropertyRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyAliases\DeserializedPatchPropertyAliasesRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyDescriptions\DeserializedPatchPropertyDescriptionsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyLabels\DeserializedPatchPropertyLabelsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyStatement\DeserializedPatchPropertyStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks\DeserializedPatchSitelinksRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription\DeserializedRemoveItemDescriptionRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemLabel\DeserializedRemoveItemLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemStatement\DeserializedRemoveItemStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyDescription\DeserializedRemovePropertyDescriptionRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyLabel\DeserializedRemovePropertyLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement\DeserializedRemovePropertyStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink\DeserializedRemoveSitelinkRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceItemStatement\DeserializedReplaceItemStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplacePropertyStatement\DeserializedReplacePropertyStatementRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemDescription\DeserializedSetItemDescriptionRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel\DeserializedSetItemLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription\DeserializedSetPropertyDescriptionRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyLabel\DeserializedSetPropertyLabelRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink\DeserializedSetSitelinkRequest;
use Wikibase\Repo\Domains\Crud\Domain\Model\UserProvidedEditMetadata;

/**
 * @license GPL-2.0-or-later
 */
class DeserializedRequestAdapter implements
	DeserializedAddItemStatementRequest,
	DeserializedAddPropertyStatementRequest,
	DeserializedGetItemRequest,
	DeserializedGetSitelinksRequest,
	DeserializedGetSitelinkRequest,
	DeserializedGetItemLabelsRequest,
	DeserializedGetItemLabelRequest,
	DeserializedGetItemLabelWithFallbackRequest,
	DeserializedGetItemDescriptionsRequest,
	DeserializedGetItemDescriptionRequest,
	DeserializedGetItemDescriptionWithFallbackRequest,
	DeserializedGetItemAliasesRequest,
	DeserializedGetItemAliasesInLanguageRequest,
	DeserializedGetItemStatementRequest,
	DeserializedGetItemStatementsRequest,
	DeserializedGetPropertyRequest,
	DeserializedGetPropertyLabelsRequest,
	DeserializedGetPropertyDescriptionsRequest,
	DeserializedGetPropertyDescriptionWithFallbackRequest,
	DeserializedGetPropertyAliasesRequest,
	DeserializedGetPropertyAliasesInLanguageRequest,
	DeserializedGetPropertyStatementRequest,
	DeserializedGetPropertyStatementsRequest,
	DeserializedPatchItemRequest,
	DeserializedPatchItemLabelsRequest,
	DeserializedPatchItemDescriptionsRequest,
	DeserializedPatchItemAliasesRequest,
	DeserializedPatchItemStatementRequest,
	DeserializedPatchPropertyStatementRequest,
	DeserializedRemoveItemLabelRequest,
	DeserializedRemoveItemDescriptionRequest,
	DeserializedRemoveItemStatementRequest,
	DeserializedRemovePropertyLabelRequest,
	DeserializedRemovePropertyDescriptionRequest,
	DeserializedRemovePropertyStatementRequest,
	DeserializedReplaceItemStatementRequest,
	DeserializedReplacePropertyStatementRequest,
	DeserializedSetItemLabelRequest,
	DeserializedSetItemDescriptionRequest,
	DeserializedGetPropertyLabelRequest,
	DeserializedGetPropertyLabelWithFallbackRequest,
	DeserializedGetPropertyDescriptionRequest,
	DeserializedSetPropertyDescriptionRequest,
	DeserializedPatchPropertyRequest,
	DeserializedPatchPropertyLabelsRequest,
	DeserializedPatchPropertyDescriptionsRequest,
	DeserializedPatchPropertyAliasesRequest,
	DeserializedSetPropertyLabelRequest,
	DeserializedAddItemAliasesInLanguageRequest,
	DeserializedAddPropertyAliasesInLanguageRequest,
	DeserializedRemoveSitelinkRequest,
	DeserializedSetSitelinkRequest,
	DeserializedPatchSitelinksRequest,
	DeserializedCreateItemRequest
{
	private array $deserializedRequest;

	public function __construct( array $deserializedRequest ) {
		$this->deserializedRequest = $deserializedRequest;
	}

	public function getItemId(): ItemId {
		return $this->getRequestField( ItemIdRequest::class );
	}

	public function getPropertyId(): NumericPropertyId {
		return $this->getRequestField( PropertyIdRequest::class );
	}

	public function getStatementId(): StatementGuid {
		return $this->getRequestField( StatementIdRequest::class );
	}

	public function getPropertyIdFilter(): ?PropertyId {
		return $this->getRequestField( PropertyIdFilterRequest::class );
	}

	public function getLanguageCode(): string {
		if ( array_key_exists( LabelLanguageCodeRequest::class, $this->deserializedRequest ) xor
			( array_key_exists( DescriptionLanguageCodeRequest::class, $this->deserializedRequest ) xor
				array_key_exists( AliasLanguageCodeRequest::class, $this->deserializedRequest ) ) ) {
			return $this->deserializedRequest[LabelLanguageCodeRequest::class]
				?? $this->deserializedRequest[DescriptionLanguageCodeRequest::class]
				?? $this->deserializedRequest[AliasLanguageCodeRequest::class];
		}

		throw new LogicException(
			'The request must be exactly one of: LabelLanguageCodeRequest, DescriptionLanguageCodeRequest, AliasLanguageCodeRequest'
		);
	}

	public function getItemFields(): array {
		return $this->getRequestField( ItemFieldsRequest::class );
	}

	public function getPropertyFields(): array {
		return $this->getRequestField( PropertyFieldsRequest::class );
	}

	public function getEditMetadata(): UserProvidedEditMetadata {
		return $this->getRequestField( EditMetadataRequest::class );
	}

	public function getStatement(): Statement {
		return $this->getRequestField( StatementSerializationRequest::class );
	}

	public function getPatch(): array {
		return $this->getRequestField( PatchRequest::class );
	}

	public function getSiteId(): string {
		return $this->getRequestField( SiteIdRequest::class );
	}

	public function getItemLabel(): Term {
		return $this->getRequestField( ItemLabelEditRequest::class );
	}

	public function getItemDescription(): Term {
		return $this->getRequestField( ItemDescriptionEditRequest::class );
	}

	public function getItemAliasesInLanguage(): array {
		return $this->getRequestField( ItemAliasesInLanguageEditRequest::class );
	}

	public function getPropertyLabel(): Term {
		return $this->getRequestField( PropertyLabelEditRequest::class );
	}

	public function getPropertyDescription(): Term {
		return $this->getRequestField( PropertyDescriptionEditRequest::class );
	}

	public function getPropertyAliasesInLanguage(): array {
		return $this->getRequestField( PropertyAliasesInLanguageEditRequest::class );
	}

	public function getSitelink(): SiteLink {
		return $this->getRequestField( SitelinkEditRequest::class );
	}

	public function getItem(): Item {
		return $this->getRequestField( ItemSerializationRequest::class );
	}

	/**
	 * @return mixed
	 */
	private function getRequestField( string $field ) {
		if ( !array_key_exists( $field, $this->deserializedRequest ) ) {
			throw new LogicException( "'$field' is not part of the request" );
		}

		return $this->deserializedRequest[$field];
	}

}
