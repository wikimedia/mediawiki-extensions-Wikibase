'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newAddPropertyStatementRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const { makeEtag } = require( '../helpers/httpHelper' );

describe( newAddPropertyStatementRequestBuilder().getRouteDescription(), () => {
	let testPropertyId;
	let testStatement;
	let originalLastModified;
	let originalRevisionId;

	function assertValid201Response( response, propertyId = null, content = null ) {
		expect( response ).to.have.status( 201 );
		assert.strictEqual( response.body.property.id, propertyId || testStatement.property.id );
		assert.deepStrictEqual( response.body.value.content, content || testStatement.value.content );
		assert.header( response, 'Location', response.request.url + '/' + encodeURIComponent( response.body.id ) );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
	}

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

	before( async () => {
		testPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
		testStatement = entityHelper.newStatementWithRandomStringValue( testPropertyId );

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '201 success response', () => {
		it( 'can add a statement to a property with edit metadata omitted', async () => {
			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, testStatement )
				.assertValidRequest().makeRequest();

			assertValid201Response( response );
		} );

		it( 'can add a statement to a property with edit metadata', async () => {
			const user = await action.robby(); // robby is a bot
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look i made an edit';
			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, testStatement )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				formatStatementEditSummary(
					'wbsetclaim',
					'create',
					testStatement.property.id,
					testStatement.value.content,
					editSummary
				)
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );

		it( 'can add a statement with a globecoordinate value in new format', async () => {
			const createPropertyResponse = await entityHelper.createEntity( 'property', {
				labels: { en: { language: 'en', value: `globe-coordinate-property-${utils.uniq()}` } },
				datatype: 'globe-coordinate'
			} );
			const statementPropertyId = createPropertyResponse.entity.id;
			const globecoordinate = {
				latitude: 100,
				longitude: 100,
				precision: 1,
				globe: 'http://www.wikidata.org/entity/Q2'
			};
			const statement = {
				property: { id: statementPropertyId },
				value: { type: 'value', content: globecoordinate }
			};

			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, statement )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, statementPropertyId, globecoordinate );
		} );

		it( 'can add a statement with a time value in new format', async () => {
			const createPropertyResponse = await entityHelper.createEntity( 'property', {
				labels: { en: { language: 'en', value: `time-property-${utils.uniq()}` } },
				datatype: 'time'
			} );
			const statementPropertyId = createPropertyResponse.entity.id;
			const time = {
				time: '+0001-00-00T00:00:00Z',
				precision: 9,
				calendarmodel: 'http://www.wikidata.org/entity/Q1985727'
			};
			const statement = {
				property: { id: statementPropertyId },
				value: { type: 'value', content: time }
			};

			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, statement )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, statementPropertyId, time );
		} );

		it( 'can add a statement with a wikibase-entityid value in new format', async () => {
			const createPropertyResponse = await entityHelper.createEntity( 'property', {
				labels: { en: { language: 'en', value: `property-${utils.uniq()}` } },
				datatype: 'wikibase-property'
			} );
			const statementPropertyId = createPropertyResponse.entity.id;
			const statement = {
				property: { id: statementPropertyId },
				value: { type: 'value', content: testPropertyId }
			};

			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, statement )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, statementPropertyId, testPropertyId );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid request data', async () => {
			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, testStatement )
				.withJsonBodyParam( 'statement', 1234 )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'statement' );
			assert.strictEqual( response.body.expectedType, 'object' );
		} );

		it( 'invalid property id', async () => {
			const propertyId = 'X123';
			const response = await newAddPropertyStatementRequestBuilder( propertyId, testStatement )
				.assertInvalidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-property-id', { 'property-id': propertyId } );
			assert.include( response.body.message, propertyId );
		} );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, testStatement )
				.withJsonBodyParam( 'comment', comment )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, testStatement )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid statement field', async () => {
			const invalidField = 'rank';
			const invalidValue = 'not-a-valid-rank';
			const invalidStatement = { ...testStatement };
			invalidStatement[ invalidField ] = invalidValue;

			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, invalidStatement )
				.assertInvalidRequest()
				.makeRequest();

			const context = { path: invalidField, value: invalidValue };
			assertValidErrorResponse( response, 400, 'statement-data-invalid-field', context );
			assert.include( response.body.message, invalidField );
		} );

		it( 'missing statement field', async () => {
			const missingField = 'value';
			const invalidStatement = { ...testStatement };
			delete invalidStatement[ missingField ];

			const response = await newAddPropertyStatementRequestBuilder( testPropertyId, invalidStatement )
				.assertInvalidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 400, 'statement-data-missing-field', { path: missingField } );
			assert.include( response.body.message, missingField );
		} );
	} );

	describe( '404 error response', () => {
		it( 'property not found', async () => {
			const propertyId = 'P999999';
			const response = await newAddPropertyStatementRequestBuilder( propertyId, testStatement )
				.assertValidRequest()
				.makeRequest();

			assertValidErrorResponse( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );
	} );
} );
