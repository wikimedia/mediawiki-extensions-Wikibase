'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newRemoveItemDescriptionsRequestBuilder,
	newSetItemDescriptionRequestBuilder,
	newGetItemDescriptionRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( newRemoveItemDescriptionsRequestBuilder().getRouteDescription(), () => {

	let testItemId;

	before( async () => {
		testItemId = ( await entityHelper.createEntity( 'item', {} ) ).entity.id;
	} );

	describe( '200 success response', () => {
		it( 'can remove a description', async () => {
			const languageCode = 'en';
			await newSetItemDescriptionRequestBuilder( testItemId, languageCode, 'english description ' + utils.uniq() )
				.makeRequest();

			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'remove english description';

			const response = await newRemoveItemDescriptionsRequestBuilder( testItemId, languageCode )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body, 'Description deleted' );
			assert.header( response, 'Content-Language', languageCode );

			const verifyDeleted = await newGetItemDescriptionRequestBuilder( testItemId, languageCode ).makeRequest();
			expect( verifyDeleted ).to.have.status( 404 );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
		} );
	} );

} );
