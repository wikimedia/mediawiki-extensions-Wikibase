<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\DataModel\Term\Term;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage\DeserializedAddItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement\DeserializedAddItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage\DeserializedAddPropertyAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement\DeserializedAddPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\CreateItem\DeserializedCreateItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItem\DeserializedGetItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliases\DeserializedGetItemAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage\DeserializedGetItemAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription\DeserializedGetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions\DeserializedGetItemDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel\DeserializedGetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabels\DeserializedGetItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemLabelWithFallback\DeserializedGetItemLabelWithFallbackRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement\DeserializedGetItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements\DeserializedGetItemStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetProperty\DeserializedGetPropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliases\DeserializedGetPropertyAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage\DeserializedGetPropertyAliasesInLanguageRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription\DeserializedGetPropertyDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescriptions\DeserializedGetPropertyDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabel\DeserializedGetPropertyLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyLabels\DeserializedGetPropertyLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement\DeserializedGetPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements\DeserializedGetPropertyStatementsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetSitelink\DeserializedGetSitelinkRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetSitelinks\DeserializedGetSitelinksRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItem\DeserializedPatchItemRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases\DeserializedPatchItemAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemDescriptions\DeserializedPatchItemDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels\DeserializedPatchItemLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement\DeserializedPatchItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchProperty\DeserializedPatchPropertyRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases\DeserializedPatchPropertyAliasesRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions\DeserializedPatchPropertyDescriptionsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels\DeserializedPatchPropertyLabelsRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement\DeserializedPatchPropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks\DeserializedPatchSitelinksRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription\DeserializedRemoveItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemLabel\DeserializedRemoveItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement\DeserializedRemoveItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription\DeserializedRemovePropertyDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel\DeserializedRemovePropertyLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement\DeserializedRemovePropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveSitelink\DeserializedRemoveSitelinkRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement\DeserializedReplaceItemStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement\DeserializedReplacePropertyStatementRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription\DeserializedSetItemDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel\DeserializedSetItemLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription\DeserializedSetPropertyDescriptionRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel\DeserializedSetPropertyLabelRequest;
use Wikibase\Repo\RestApi\Application\UseCases\SetSitelink\DeserializedSetSitelinkRequest;
use Wikibase\Repo\RestApi\Domain\Model\UserProvidedEditMetadata;

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
	DeserializedGetItemAliasesRequest,
	DeserializedGetItemAliasesInLanguageRequest,
	DeserializedGetItemStatementRequest,
	DeserializedGetItemStatementsRequest,
	DeserializedGetPropertyRequest,
	DeserializedGetPropertyLabelsRequest,
	DeserializedGetPropertyDescriptionsRequest,
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
