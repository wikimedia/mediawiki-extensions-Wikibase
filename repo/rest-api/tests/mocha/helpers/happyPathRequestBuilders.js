'use strict';

const rbf = require( './RequestBuilderFactory' );
const { newStatementWithRandomStringValue } = require( './entityHelper' );
const { utils } = require( 'api-testing' );

module.exports.getRequestsOnItem = [
	( { itemId } ) => rbf.newGetItemStatementsRequestBuilder( itemId ),
	( { itemId, statementId } ) => rbf.newGetItemStatementRequestBuilder( itemId, statementId ),
	( { itemId } ) => rbf.newGetItemRequestBuilder( itemId ),
	( { itemId } ) => rbf.newGetItemAliasesInLanguageRequestBuilder( itemId, 'en' ),
	( { itemId } ) => rbf.newGetItemAliasesRequestBuilder( itemId ),
	( { itemId } ) => rbf.newGetItemDescriptionRequestBuilder( itemId, 'en' ),
	( { itemId } ) => rbf.newGetItemDescriptionsRequestBuilder( itemId ),
	( { itemId } ) => rbf.newGetItemLabelRequestBuilder( itemId, 'en' ),
	( { itemId } ) => rbf.newGetItemLabelsRequestBuilder( itemId ),
	( { statementId } ) => rbf.newGetStatementRequestBuilder( statementId )
];

module.exports.getRequestsOnProperty = [
	( { propertyId } ) => rbf.newGetPropertyRequestBuilder( propertyId ),
	( { propertyId } ) => rbf.newGetPropertyLabelRequestBuilder( propertyId, 'en' ),
	( { propertyId } ) => rbf.newGetPropertyLabelsRequestBuilder( propertyId ),
	( { propertyId } ) => rbf.newGetPropertyDescriptionsRequestBuilder( propertyId ),
	( { propertyId } ) => rbf.newGetPropertyDescriptionRequestBuilder( propertyId, 'en' ),
	( { propertyId } ) => rbf.newGetPropertyAliasesRequestBuilder( propertyId ),
	( { propertyId } ) => rbf.newGetPropertyAliasesInLanguageRequestBuilder( propertyId, 'en' ),
	( { propertyId } ) => rbf.newGetPropertyStatementsRequestBuilder( propertyId ),
	( { statementId } ) => rbf.newGetStatementRequestBuilder( statementId ),
	( { propertyId, statementId } ) => rbf.newGetPropertyStatementRequestBuilder( propertyId, statementId )
];

module.exports.editRequestsOnProperty = [
	( { propertyId, statementPropertyId } ) => rbf.newAddPropertyStatementRequestBuilder(
		propertyId,
		newStatementWithRandomStringValue( statementPropertyId )
	),
	( { propertyId, statementId, statementPropertyId } ) => rbf.newReplacePropertyStatementRequestBuilder(
		propertyId,
		statementId,
		newStatementWithRandomStringValue( statementPropertyId )
	),
	( { statementId, statementPropertyId } ) => rbf.newReplaceStatementRequestBuilder(
		statementId,
		newStatementWithRandomStringValue( statementPropertyId )
	),
	( { propertyId, statementId } ) => rbf.newPatchPropertyStatementRequestBuilder(
		propertyId,
		statementId,
		[ {
			op: 'replace',
			path: '/value/content',
			value: 'random-string-value-' + utils.uniq()
		} ]
	),
	( { propertyId, statementId } ) => rbf.newRemovePropertyStatementRequestBuilder( propertyId, statementId ),
	( { statementId } ) => rbf.newRemoveStatementRequestBuilder( statementId ),
	( { propertyId } ) => rbf.newSetPropertyLabelRequestBuilder( propertyId, 'en', 'random-label-' + utils.uniq() ),
	( { propertyId } ) => rbf.newSetPropertyDescriptionRequestBuilder(
		propertyId,
		'en',
		'random-description-' + utils.uniq()
	)

];

module.exports.editRequestsOnItem = [
	( { itemId, statementPropertyId } ) => rbf.newAddItemStatementRequestBuilder(
		itemId,
		newStatementWithRandomStringValue( statementPropertyId )
	),
	( { itemId, statementId, statementPropertyId } ) => rbf.newReplaceItemStatementRequestBuilder(
		itemId,
		statementId,
		newStatementWithRandomStringValue( statementPropertyId )
	),
	( { statementId, statementPropertyId } ) => rbf.newReplaceStatementRequestBuilder(
		statementId,
		newStatementWithRandomStringValue( statementPropertyId )
	),
	( { itemId, statementId } ) => rbf.newRemoveItemStatementRequestBuilder( itemId, statementId ),
	( { statementId } ) => rbf.newRemoveStatementRequestBuilder( statementId ),
	( { itemId, statementId } ) => rbf.newPatchItemStatementRequestBuilder(
		itemId,
		statementId,
		[ {
			op: 'replace',
			path: '/value/content',
			value: 'random-string-value-' + utils.uniq()
		} ]
	),
	( { statementId } ) => rbf.newPatchStatementRequestBuilder(
		statementId,
		[ {
			op: 'replace',
			path: '/value/content',
			value: 'random-string-value-' + utils.uniq()
		} ]
	),
	( { itemId } ) => rbf.newSetItemLabelRequestBuilder( itemId, 'en', 'random-test-label-' + utils.uniq() ),
	( { itemId } ) => rbf.newSetItemDescriptionRequestBuilder(
		itemId,
		'en',
		'random-test-description-' + utils.uniq()
	),
	( { itemId } ) => rbf.newPatchItemLabelsRequestBuilder(
		itemId,
		[ {
			op: 'replace',
			path: '/en',
			value: 'random-test-label-' + utils.uniq()
		} ]
	),
	( { itemId } ) => rbf.newPatchItemDescriptionsRequestBuilder(
		itemId,
		[ { op: 'replace', path: '/en', value: 'random-test-description-' + utils.uniq() } ]
	)
];
