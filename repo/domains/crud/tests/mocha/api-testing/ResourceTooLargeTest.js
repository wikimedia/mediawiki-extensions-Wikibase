'use strict';

const { assert } = require( 'api-testing' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { newStatementWithRandomStringValue, createUniqueStringProperty } = require( '../helpers/entityHelper' );
const {
	newPatchPropertyRequestBuilder,
	newPatchItemRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

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
describe( 'resource too large', () => {
	let propertyId;
	let itemId;
	const fiveThousandStatements = [];
	const maxSizeInKb = 1;

	before( async () => {
		itemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		propertyId = ( await createUniqueStringProperty() ).body.id;
		for ( let i = 0; i < 5000; i++ ) {
			fiveThousandStatements.push( newStatementWithRandomStringValue( propertyId ) );
		}
	} );

	it( 'resource (item) is too large', async () => {
		const response = await newPatchItemRequestBuilder(
			itemId,
			[ { op: 'add', path: `/statements/${propertyId}`, value: fiveThousandStatements } ]
		)
			.withConfigOverride( 'wgWBRepoSettings', { maxSerializedEntitySize: maxSizeInKb } )
			.assertValidRequest()
			.makeRequest();

		assertResourceTooLargeResponse( response, maxSizeInKb );
	} );

	it( 'resource (property) is too large', async () => {
		const response = await newPatchPropertyRequestBuilder(
			propertyId,
			[ { op: 'add', path: `/statements/${propertyId}`, value: fiveThousandStatements } ]
		)
			.withConfigOverride( 'wgWBRepoSettings', { maxSerializedEntitySize: maxSizeInKb } )
			.assertValidRequest()
			.makeRequest();

		assertResourceTooLargeResponse( response, maxSizeInKb );
	} );
} );
