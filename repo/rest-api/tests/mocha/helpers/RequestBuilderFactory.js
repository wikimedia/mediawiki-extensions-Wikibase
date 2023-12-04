'use strict';

const { RequestBuilder } = require( './RequestBuilder' );
module.exports = {

	newGetItemRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}' )
			.withPathParam( 'item_id', itemId );
	},

	newGetPropertyRequestBuilder( propertyId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}' )
			.withPathParam( 'property_id', propertyId );
	},

	newGetItemAliasesRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/aliases' )
			.withPathParam( 'item_id', itemId );
	},

	newGetPropertyAliasesRequestBuilder( propertyId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}/aliases' )
			.withPathParam( 'property_id', propertyId );
	},

	newGetItemAliasesInLanguageRequestBuilder( itemId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/aliases/{language_code}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'language_code', languageCode );
	},

	newGetPropertyAliasesInLanguageRequestBuilder( propertyId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}/aliases/{language_code}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'language_code', languageCode );
	},

	newGetItemDescriptionRequestBuilder( itemId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/descriptions/{language_code}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'language_code', languageCode );
	},

	newGetPropertyDescriptionRequestBuilder( propertyId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}/descriptions/{language_code}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'language_code', languageCode );
	},

	newGetItemDescriptionsRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/descriptions' )
			.withPathParam( 'item_id', itemId );
	},

	newGetPropertyDescriptionsRequestBuilder( propertyId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}/descriptions' )
			.withPathParam( 'property_id', propertyId );
	},

	newGetItemLabelsRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/labels' )
			.withPathParam( 'item_id', itemId );
	},

	newGetPropertyLabelsRequestBuilder( propertyId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}/labels' )
			.withPathParam( 'property_id', propertyId );
	},

	newPatchItemLabelsRequestBuilder( itemId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/entities/items/{item_id}/labels' )
			.withPathParam( 'item_id', itemId )
			.withJsonBodyParam( 'patch', patch );
	},

	newPatchPropertyLabelsRequestBuilder( propertyId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/entities/properties/{property_id}/labels' )
			.withPathParam( 'property_id', propertyId )
			.withJsonBodyParam( 'patch', patch );
	},

	newPatchPropertyDescriptionsRequestBuilder( propertyId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/entities/properties/{property_id}/descriptions' )
			.withPathParam( 'property_id', propertyId )
			.withJsonBodyParam( 'patch', patch );
	},

	newPatchItemDescriptionsRequestBuilder( itemId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/entities/items/{item_id}/descriptions' )
			.withPathParam( 'item_id', itemId )
			.withJsonBodyParam( 'patch', patch );
	},

	newPatchItemAliasesRequestBuilder( itemId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/entities/items/{item_id}/aliases' )
			.withPathParam( 'item_id', itemId )
			.withJsonBodyParam( 'patch', patch );
	},

	newPatchPropertyAliasesRequestBuilder( propertyId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/entities/properties/{property_id}/aliases' )
			.withPathParam( 'property_id', propertyId )
			.withJsonBodyParam( 'patch', patch );
	},

	newGetItemLabelRequestBuilder( itemId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/labels/{language_code}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'language_code', languageCode );
	},

	newGetPropertyLabelRequestBuilder( propertyId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}/labels/{language_code}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'language_code', languageCode );
	},

	newSetItemLabelRequestBuilder( itemId, languageCode, label ) {
		return new RequestBuilder()
			.withRoute( 'PUT', '/entities/items/{item_id}/labels/{language_code}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'language_code', languageCode )
			.withJsonBodyParam( 'label', label );
	},

	newSetPropertyLabelRequestBuilder( propertyId, languageCode, label ) {
		return new RequestBuilder()
			.withRoute( 'PUT', '/entities/properties/{property_id}/labels/{language_code}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'language_code', languageCode )
			.withJsonBodyParam( 'label', label );
	},

	newSetItemDescriptionRequestBuilder( itemId, languageCode, description ) {
		return new RequestBuilder()
			.withRoute( 'PUT', '/entities/items/{item_id}/descriptions/{language_code}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'language_code', languageCode )
			.withJsonBodyParam( 'description', description );
	},

	newSetPropertyDescriptionRequestBuilder( propertyId, languageCode, description ) {
		return new RequestBuilder()
			.withRoute( 'PUT', '/entities/properties/{property_id}/descriptions/{language_code}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'language_code', languageCode )
			.withJsonBodyParam( 'description', description );
	},

	newAddItemAliasesInLanguageRequestBuilder( itemId, languageCode, aliases ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/entities/items/{item_id}/aliases/{language_code}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'language_code', languageCode )
			.withJsonBodyParam( 'aliases', aliases );
	},

	newRemovePropertyLabelRequestBuilder( propertyId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'DELETE', '/entities/properties/{property_id}/labels/{language_code}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'language_code', languageCode );
	},

	newRemoveItemLabelRequestBuilder( itemId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'DELETE', '/entities/items/{item_id}/labels/{language_code}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'language_code', languageCode );
	},

	newRemoveItemDescriptionRequestBuilder( itemId, languageCode ) {
		return new RequestBuilder()
			.withRoute( 'DELETE', '/entities/items/{item_id}/descriptions/{language_code}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'language_code', languageCode );
	},

	newGetItemStatementsRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/statements' )
			.withPathParam( 'item_id', itemId );
	},

	newGetPropertyStatementsRequestBuilder( propertyId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}/statements' )
			.withPathParam( 'property_id', propertyId );
	},

	newAddItemStatementRequestBuilder( itemId, statement ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/entities/items/{item_id}/statements' )
			.withPathParam( 'item_id', itemId )
			.withJsonBodyParam( 'statement', statement );
	},

	newAddPropertyStatementRequestBuilder( propertyId, statement ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/entities/properties/{property_id}/statements' )
			.withPathParam( 'property_id', propertyId )
			.withJsonBodyParam( 'statement', statement );
	},

	newGetItemStatementRequestBuilder( itemId, statementId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/statements/{statement_id}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'statement_id', statementId );
	},

	newGetPropertyStatementRequestBuilder( propertyId, statementId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/properties/{property_id}/statements/{statement_id}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'statement_id', statementId );
	},

	newGetStatementRequestBuilder( statementId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId );
	},

	newReplaceItemStatementRequestBuilder( itemId, statementId, statement ) {
		return new RequestBuilder()
			.withRoute( 'PUT', '/entities/items/{item_id}/statements/{statement_id}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'statement', statement );
	},

	newReplacePropertyStatementRequestBuilder( propertyId, statementId, statement ) {
		return new RequestBuilder()
			.withRoute( 'PUT', '/entities/properties/{property_id}/statements/{statement_id}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'statement', statement );
	},

	newReplaceStatementRequestBuilder( statementId, statement ) {
		return new RequestBuilder()
			.withRoute( 'PUT', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'statement', statement );
	},

	newRemoveItemStatementRequestBuilder( itemId, statementId ) {
		return new RequestBuilder()
			.withRoute( 'DELETE', '/entities/items/{item_id}/statements/{statement_id}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'statement_id', statementId );
	},

	newRemovePropertyStatementRequestBuilder( propertyId, statementId ) {
		return new RequestBuilder()
			.withRoute( 'DELETE', '/entities/properties/{property_id}/statements/{statement_id}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'statement_id', statementId );
	},

	newRemoveStatementRequestBuilder( statementId ) {
		return new RequestBuilder()
			.withRoute( 'DELETE', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId );
	},

	newPatchItemStatementRequestBuilder( itemId, statementId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/entities/items/{item_id}/statements/{statement_id}' )
			.withPathParam( 'item_id', itemId )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'patch', patch );
	},

	newPatchPropertyStatementRequestBuilder( propertyId, statementId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/entities/properties/{property_id}/statements/{statement_id}' )
			.withPathParam( 'property_id', propertyId )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'patch', patch );
	},

	newPatchStatementRequestBuilder( statementId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'patch', patch );
	}

};
