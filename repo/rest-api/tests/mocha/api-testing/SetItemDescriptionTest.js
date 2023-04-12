'use strict';

const { assert, utils, action } = require( 'api-testing' );
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

	function assertValid200Response( response, description ) {
		assert.strictEqual( response.status, 200 );
		assert.strictEqual( response.body, description );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

	describe( '20x success', () => {
		it( 'can replace a description with edit metadata omitted', async () => {
			const description = `new description ${utils.uniq()}`;
			const languageCode = 'en';
			const response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, description );
		} );

		it( 'can replace a description with edit metadata provided', async () => {
			const description = `new description ${utils.uniq()}`;
			const languageCode = 'en';
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );

			const response = await newSetItemDescriptionRequestBuilder( testItemId, languageCode, description )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid200Response( response, description );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
		} );
	} );

} );
