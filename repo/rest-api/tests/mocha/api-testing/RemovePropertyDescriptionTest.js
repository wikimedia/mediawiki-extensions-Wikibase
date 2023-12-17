'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const {
	newRemovePropertyDescriptionRequestBuilder,
	newSetPropertyDescriptionRequestBuilder,
	newGetPropertyDescriptionRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { formatTermEditSummary } = require( '../helpers/formatEditSummaries' );

function assertValidErrorResponse( response, statusCode, responseBodyErrorCode, context = null ) {
	expect( response ).to.have.status( statusCode );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
	if ( context === null ) {
		assert.notProperty( response.body, 'context' );
	} else {
		assert.deepStrictEqual( response.body.context, context );
	}
}

describe( newRemovePropertyDescriptionRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;

	before( async () => {
		testPropertyId = ( await entityHelper.createEntity( 'property', { datatype: 'string' } ) ).entity.id;
	} );

	describe( '200 success response', () => {
		it( 'can remove a description without edit metadata', async () => {
			const languageCode = 'en';
			const description = `english description ${utils.uniq()}`;
			await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, description ).makeRequest();

			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, languageCode )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body, 'Description deleted' );
			assert.header( response, 'Content-Language', languageCode );

			const verifyDeleted = await newGetPropertyDescriptionRequestBuilder( testPropertyId, languageCode )
				.makeRequest();
			expect( verifyDeleted ).to.have.status( 404 );
		} );

		it( 'can remove a description with metadata', async () => {
			const languageCode = 'en';
			const description = `english description ${utils.uniq()}`;
			await newSetPropertyDescriptionRequestBuilder( testPropertyId, languageCode, description ).makeRequest();

			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const comment = 'remove english description';

			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, languageCode )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', comment )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body, 'Description deleted' );
			assert.header( response, 'Content-Language', languageCode );

			const verifyDeleted = await newGetPropertyDescriptionRequestBuilder( testPropertyId, languageCode )
				.makeRequest();
			expect( verifyDeleted ).to.have.status( 404 );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.include( editMetadata.tags, tag );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatTermEditSummary( 'wbsetdescription', 'remove', languageCode, description, comment )
			);
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid property id', async () => {
			const propertyId = testPropertyId.replace( 'P', 'Q' );
			const response = await newRemovePropertyDescriptionRequestBuilder( propertyId, 'en' )
				.assertInvalidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-property-id', { 'property-id': propertyId } );
			assert.include( response.body.message, propertyId );
		} );

		it( 'invalid language code', async () => {
			const invalidLanguageCode = 'xyz';
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, invalidLanguageCode )
				.assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-language-code' );
			assert.include( response.body.message, invalidLanguageCode );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidErrorResponse( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );
	} );

} );
