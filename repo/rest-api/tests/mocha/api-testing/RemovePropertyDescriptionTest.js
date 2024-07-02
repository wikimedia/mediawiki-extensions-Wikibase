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
const { assertValidError } = require( '../helpers/responseValidator' );

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
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId.replace( 'P', 'Q' ), 'en' )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'property_id' }
			);
		} );

		it( 'invalid language code', async () => {
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'xyz' )
				.assertValidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'language_code' }
			);
		} );

		it( 'invalid edit tag', async () => {
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/tags' } );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, 'en' )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
		} );
	} );

	describe( '404 error response', () => {
		it( 'property not found', async () => {
			const propertyId = 'P999999';
			const response = await newRemovePropertyDescriptionRequestBuilder( propertyId, 'en' )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

		it( 'description in the language specified does not exist', async () => {
			const languageCode = 'ar';
			const response = await newRemovePropertyDescriptionRequestBuilder( testPropertyId, languageCode )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'description-not-defined' );
			assert.include( response.body.message, testPropertyId );
			assert.include( response.body.message, languageCode );
		} );
	} );
} );
