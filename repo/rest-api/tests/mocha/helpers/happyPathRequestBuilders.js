'use strict';

const rbf = require( './RequestBuilderFactory' );
const { newStatementWithRandomStringValue } = require( './entityHelper' );
const { utils } = require( 'api-testing' );

module.exports.getItemGetRequests = ( requestInputs ) => ( [
	() => rbf.newGetItemStatementsRequestBuilder( requestInputs.itemId ),
	() => rbf.newGetItemStatementRequestBuilder( requestInputs.itemId, requestInputs.statementId ),
	() => rbf.newGetItemRequestBuilder( requestInputs.itemId ),
	() => rbf.newGetItemAliasesInLanguageRequestBuilder( requestInputs.itemId, 'en' ),
	() => rbf.newGetItemAliasesRequestBuilder( requestInputs.itemId ),
	() => rbf.newGetItemDescriptionRequestBuilder( requestInputs.itemId, 'en' ),
	() => rbf.newGetItemDescriptionWithFallbackRequestBuilder( requestInputs.itemId, 'en' ),
	() => rbf.newGetItemDescriptionsRequestBuilder( requestInputs.itemId ),
	() => rbf.newGetItemLabelRequestBuilder( requestInputs.itemId, 'en' ),
	() => rbf.newGetItemLabelWithFallbackRequestBuilder( requestInputs.itemId, 'en' ),
	() => rbf.newGetItemLabelsRequestBuilder( requestInputs.itemId ),
	() => rbf.newGetStatementRequestBuilder( requestInputs.statementId ),
	() => rbf.newGetSitelinksRequestBuilder( requestInputs.itemId ),
	() => rbf.newGetSitelinkRequestBuilder( requestInputs.itemId, requestInputs.siteId )
].map( ( newRequestBuilder ) => ( { newRequestBuilder, requestInputs } ) ) );

module.exports.getPropertyGetRequests = ( requestInputs ) => ( [
	() => rbf.newGetPropertyRequestBuilder( requestInputs.propertyId ),
	() => rbf.newGetPropertyLabelRequestBuilder( requestInputs.propertyId, 'en' ),
	() => rbf.newGetPropertyLabelWithFallbackRequestBuilder( requestInputs.propertyId, 'en' ),
	() => rbf.newGetPropertyLabelsRequestBuilder( requestInputs.propertyId ),
	() => rbf.newGetPropertyDescriptionsRequestBuilder( requestInputs.propertyId ),
	() => rbf.newGetPropertyDescriptionRequestBuilder( requestInputs.propertyId, 'en' ),
	() => rbf.newGetPropertyDescriptionWithFallbackRequestBuilder( requestInputs.propertyId, 'en' ),
	() => rbf.newGetPropertyAliasesRequestBuilder( requestInputs.propertyId ),
	() => rbf.newGetPropertyAliasesInLanguageRequestBuilder( requestInputs.propertyId, 'en' ),
	() => rbf.newGetPropertyStatementsRequestBuilder( requestInputs.propertyId ),
	() => rbf.newGetStatementRequestBuilder( requestInputs.statementId ),
	() => rbf.newGetPropertyStatementRequestBuilder( requestInputs.propertyId, requestInputs.statementId )
].map( ( newRequestBuilder ) => ( { newRequestBuilder, requestInputs } ) ) );

module.exports.getPropertyEditRequests = ( requestInputs ) => ( [
	() => rbf.newPatchPropertyLabelsRequestBuilder(
		requestInputs.propertyId,
		[ {
			op: 'replace',
			path: '/en',
			value: 'en-label-' + utils.uniq()
		} ]
	),
	() => rbf.newPatchPropertyDescriptionsRequestBuilder(
		requestInputs.propertyId,
		[ { op: 'replace', path: '/en', value: 'random-test-description-' + utils.uniq() } ]
	),
	() => rbf.newPatchPropertyAliasesRequestBuilder(
		requestInputs.propertyId,
		[ {
			op: 'replace',
			path: '/en',
			value: [ 'en-alias-' + utils.uniq() ]
		} ]
	),
	() => rbf.newAddPropertyAliasesInLanguageRequestBuilder(
		requestInputs.propertyId,
		'en',
		[ 'en-alias-' + utils.uniq() ]
	),
	() => rbf.newAddPropertyStatementRequestBuilder(
		requestInputs.propertyId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	),
	() => rbf.newReplacePropertyStatementRequestBuilder(
		requestInputs.propertyId,
		requestInputs.statementId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	),
	() => rbf.newReplaceStatementRequestBuilder(
		requestInputs.statementId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	),
	() => rbf.newPatchPropertyStatementRequestBuilder(
		requestInputs.propertyId,
		requestInputs.statementId,
		[ {
			op: 'replace',
			path: '/value/content',
			value: 'random-string-value-' + utils.uniq()
		} ]
	),
	() => rbf.newRemovePropertyStatementRequestBuilder( requestInputs.propertyId, requestInputs.statementId ),
	() => rbf.newRemoveStatementRequestBuilder( requestInputs.statementId ),
	() => rbf.newSetPropertyLabelRequestBuilder( requestInputs.propertyId, 'en', 'random-label-' + utils.uniq() ),
	() => rbf.newSetPropertyDescriptionRequestBuilder(
		requestInputs.propertyId,
		'en',
		'random-description-' + utils.uniq()
	),
	() => rbf.newRemovePropertyLabelRequestBuilder( requestInputs.propertyId, 'en' ),
	() => rbf.newRemovePropertyDescriptionRequestBuilder( requestInputs.propertyId, 'en' ),
	() => rbf.newPatchPropertyRequestBuilder(
		requestInputs.propertyId,
		[ {
			op: 'replace',
			path: '/labels/en',
			value: 'en-label' + utils.uniq()
		} ]
	)
].map( ( newRequestBuilder ) => ( { newRequestBuilder, requestInputs } ) ) );

module.exports.getItemEditRequests = ( requestInputs ) => ( [
	() => rbf.newAddItemStatementRequestBuilder(
		requestInputs.itemId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	),
	() => rbf.newReplaceItemStatementRequestBuilder(
		requestInputs.itemId,
		requestInputs.statementId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	),
	() => rbf.newReplaceStatementRequestBuilder(
		requestInputs.statementId,
		newStatementWithRandomStringValue( requestInputs.statementPropertyId )
	),
	() => rbf.newRemoveItemStatementRequestBuilder( requestInputs.itemId, requestInputs.statementId ),
	() => rbf.newRemoveStatementRequestBuilder( requestInputs.statementId ),
	() => rbf.newPatchItemStatementRequestBuilder(
		requestInputs.itemId,
		requestInputs.statementId,
		[ {
			op: 'replace',
			path: '/value/content',
			value: 'random-string-value-' + utils.uniq()
		} ]
	),
	() => rbf.newPatchStatementRequestBuilder(
		requestInputs.statementId,
		[ {
			op: 'replace',
			path: '/value/content',
			value: 'random-string-value-' + utils.uniq()
		} ]
	),
	() => rbf.newSetItemLabelRequestBuilder( requestInputs.itemId, 'en', 'random-test-label-' + utils.uniq() ),
	() => rbf.newSetItemDescriptionRequestBuilder(
		requestInputs.itemId,
		'en',
		'random-test-description-' + utils.uniq()
	),
	() => rbf.newSetSitelinkRequestBuilder(
		requestInputs.itemId,
		requestInputs.siteId,
		{ title: requestInputs.linkedArticle }
	),
	() => rbf.newPatchSitelinksRequestBuilder(
		requestInputs.itemId,
		[ {
			op: 'remove',
			path: `/${requestInputs.siteId}/badges`
		} ]

	),
	() => rbf.newPatchItemLabelsRequestBuilder(
		requestInputs.itemId,
		[ {
			op: 'replace',
			path: '/en',
			value: 'random-test-label-' + utils.uniq()
		} ]
	),
	() => rbf.newPatchItemDescriptionsRequestBuilder(
		requestInputs.itemId,
		[ { op: 'replace', path: '/en', value: 'random-test-description-' + utils.uniq() } ]
	),
	() => rbf.newPatchItemAliasesRequestBuilder(
		requestInputs.itemId,
		[ { op: 'replace', path: '/en', value: [ 'en-alias-' + utils.uniq() ] } ]
	),
	() => rbf.newAddItemAliasesInLanguageRequestBuilder( requestInputs.itemId, 'en', [ 'en-alias-' + utils.uniq() ] ),
	() => rbf.newRemoveItemLabelRequestBuilder( requestInputs.itemId, 'en' ),
	() => rbf.newRemoveItemDescriptionRequestBuilder( requestInputs.itemId, 'en' ),
	() => rbf.newRemoveSitelinkRequestBuilder( requestInputs.itemId, requestInputs.siteId ),
	() => rbf.newPatchItemRequestBuilder(
		requestInputs.itemId,
		[ {
			op: 'replace',
			path: '/labels/en',
			value: 'en-label' + utils.uniq()
		} ]
	)
].map( ( newRequestBuilder ) => ( { newRequestBuilder, requestInputs } ) ) );

module.exports.getItemCreateRequest = ( requestInput ) => ( {
	newRequestBuilder: () => rbf.newCreateItemRequestBuilder( { labels: { en: 'new Item' } } ),
	requestInput
} );
