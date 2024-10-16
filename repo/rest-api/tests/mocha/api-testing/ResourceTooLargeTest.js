'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { assert } = require( 'api-testing' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { newStatementWithRandomStringValue } = require( '../helpers/entityHelper' );
const { newPatchPropertyRequestBuilder, newPatchItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );

function assertResourceTooLargeResponse( response, maxSizeInKb ) {
	assertValidError(
		response,
		400,
		'resource-too-large',
		{ limit: maxSizeInKb }
	);
	assert.strictEqual(
		response.body.message,
		`Edit resulted in a resource that exceeds the size limit of ${maxSizeInKb.toString()} kB`
	);
}

// This test is focused on patching an item and patching a property endpoints only.
// Testing other cases has been deemed too complex for now and can be revisited in the future
// if it's determined that the extra effort is worthwhile.
//
// Note: If we need to check whether a resource (entity) is too large, we can use the
// action API to get the entity size. Here's a helpful link for that:
// https://www.wikidata.org/w/api.php?action=query&format=json&prop=info&titles={entity_id}&formatversion=2
describeWithTestData( 'Resource too large tests', ( itemRequestInputs, propertyRequestInputs ) => {
	describe( 'resource too large', () => {
		let predicatePropertyId;
		const fiveThousandStatements = [];
		const maxSizeInKb = 1;

		before( async () => {
			predicatePropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			for ( let i = 0; i < 5000; i++ ) {
				fiveThousandStatements.push( newStatementWithRandomStringValue( predicatePropertyId ) );
			}
		} );

		it( 'resource (item) is too large', async () => {
			const response = await newPatchItemRequestBuilder(
				itemRequestInputs.itemId,
				[ { op: 'add', path: `/statements/${predicatePropertyId}`, value: fiveThousandStatements } ]
			)
				.withConfigOverride( 'wgWBRepoSettings', { maxSerializedEntitySize: maxSizeInKb } )
				.assertValidRequest()
				.makeRequest();

			assertResourceTooLargeResponse( response, maxSizeInKb );
		} );

		it( 'resource (property) is too large', async () => {
			const response = await newPatchPropertyRequestBuilder(
				propertyRequestInputs.propertyId,
				[ { op: 'add', path: `/statements/${predicatePropertyId}`, value: fiveThousandStatements } ]
			)
				.withConfigOverride( 'wgWBRepoSettings', { maxSerializedEntitySize: maxSizeInKb } )
				.assertValidRequest()
				.makeRequest();

			assertResourceTooLargeResponse( response, maxSizeInKb );
		} );
	} );
} );
