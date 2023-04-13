'use strict';

const { assert, utils } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { newSetItemDescriptionRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( newSetItemDescriptionRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let originalLastModified;
	let originalRevisionId;

	before( async () => {
		const createEntityResponse = await entityHelper.createEntity( 'item', {
			descriptions: [ { language: 'en', value: `some-description-${utils.uniq()}` } ]
		} );
		testItemId = createEntityResponse.entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		// wait 1s before modifying to verify the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	it( 'can replace a description', async () => {
		const description = 'new description';
		const languageCode = 'en';
		const response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description )
			.assertValidRequest()
			.makeRequest();

		assert.strictEqual( response.status, 200 );
		assert.strictEqual( response.body, description );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	} );

} );
