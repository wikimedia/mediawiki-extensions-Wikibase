'use strict';

const { RequestBuilder } = require( './RequestBuilder' );
module.exports = {

	newGetItemRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}' )
			.withPathParam( 'item_id', itemId );
	},

	newGetItemAliasesRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/aliases' )
			.withPathParam( 'item_id', itemId );
	},

	newGetItemDescriptionsRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/descriptions' )
			.withPathParam( 'item_id', itemId );
	},

	newGetItemLabelsRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/labels' )
			.withPathParam( 'item_id', itemId );
	},

	newGetItemStatementsRequestBuilder( itemId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/statements' )
			.withPathParam( 'item_id', itemId );
	},

	newAddItemStatementRequestBuilder( itemId, statement ) {
		return new RequestBuilder()
			.withRoute( 'POST', '/entities/items/{item_id}/statements' )
			.withPathParam( 'item_id', itemId )
			.withJsonBodyParam( 'statement', statement );
	},

	newGetItemStatementRequestBuilder( itemId, statementId ) {
		return new RequestBuilder()
			.withRoute( 'GET', '/entities/items/{item_id}/statements/{statement_id}' )
			.withPathParam( 'item_id', itemId )
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

	newPatchStatementRequestBuilder( statementId, patch ) {
		return new RequestBuilder()
			.withRoute( 'PATCH', '/statements/{statement_id}' )
			.withPathParam( 'statement_id', statementId )
			.withJsonBodyParam( 'patch', patch );
	}

};
