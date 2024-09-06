'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { formatStatementEditSummary } = require( '../helpers/formatEditSummaries' );
const { newAddItemStatementRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newAddItemStatementRequestBuilder().getRouteDescription(), () => {
	let testItemId;
	let originalLastModified;
	let originalRevisionId;
	let testStatement;

	function assertValid201Response( response, propertyId = null, content = null ) {
		expect( response ).to.have.status( 201 );
		assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
		assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
		assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );
		assert.header( response, 'Location', response.request.url + '/' + encodeURIComponent( response.body.id ) );
		assert.strictEqual( response.body.property.id, propertyId || testStatement.property.id );
		assert.deepStrictEqual( response.body.value.content, content || testStatement.value.content );
	}

	before( async () => {
		const createEntityResponse = await entityHelper.createEntity( 'item', {} );
		testItemId = createEntityResponse.entity.id;

		const testItemCreationMetadata = await entityHelper.getLatestEditMetadata( testItemId );
		originalLastModified = new Date( testItemCreationMetadata.timestamp );
		originalRevisionId = testItemCreationMetadata.revid;

		testStatement = entityHelper.newStatementWithRandomStringValue(
			( await entityHelper.createUniqueStringProperty() ).entity.id
		);

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '201 success response ', () => {
		it( 'can add a statement to an item with edit metadata omitted', async () => {
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response );

			const { comment } = await entityHelper.getLatestEditMetadata( testItemId );
			assert.strictEqual(
				comment,
				formatStatementEditSummary(
					'wbsetclaim',
					'create',
					testStatement.property.id,
					testStatement.value.content
				)
			);
		} );
		it( 'can add a statement to an item with edit metadata provided', async () => {
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test', true );
			const editSummary = 'omg look i made an edit';
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response );

			const editMetadata = await entityHelper.getLatestEditMetadata( testItemId );
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
			const propertyId = createPropertyResponse.entity.id;
			const globecoordinate = {
				latitude: 100,
				longitude: 100,
				precision: 1,
				globe: 'http://www.wikidata.org/entity/Q2'
			};
			const statement = {
				property: { id: propertyId },
				value: { type: 'value', content: globecoordinate }
			};

			const response = await newAddItemStatementRequestBuilder( testItemId, statement )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, propertyId, globecoordinate );
		} );
		it( 'can add a statement with a time value in new format', async () => {
			const createPropertyResponse = await entityHelper.createEntity( 'property', {
				labels: { en: { language: 'en', value: `time-property-${utils.uniq()}` } },
				datatype: 'time'
			} );
			const propertyId = createPropertyResponse.entity.id;
			const time = {
				time: '+0001-00-00T00:00:00Z',
				precision: 9,
				calendarmodel: 'http://www.wikidata.org/entity/Q1985727'
			};
			const statement = {
				property: { id: propertyId },
				value: { type: 'value', content: time }
			};

			const response = await newAddItemStatementRequestBuilder( testItemId, statement )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, propertyId, time );
		} );
		it( 'can add a statement with a wikibase-entityid value in new format', async () => {
			const createPropertyResponse = await entityHelper.createEntity( 'property', {
				labels: { en: { language: 'en', value: `wikibase-item-property-${utils.uniq()}` } },
				datatype: 'wikibase-item'
			} );
			const propertyId = createPropertyResponse.entity.id;
			const statement = {
				property: { id: propertyId },
				value: { type: 'value', content: testItemId }
			};

			const response = await newAddItemStatementRequestBuilder( testItemId, statement )
				.assertValidRequest()
				.makeRequest();

			assertValid201Response( response, propertyId, testItemId );
		} );
	} );

	describe( '400 error response', () => {
		it( 'invalid Item ID', async () => {
			const itemId = 'X123';
			const response = await newAddItemStatementRequestBuilder( itemId, testStatement )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError(
				response,
				400,
				'invalid-path-parameter',
				{ parameter: 'item_id' }
			);
		} );

		it( 'comment too long', async () => {
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.withJsonBodyParam( 'comment', 'x'.repeat( 501 ) )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'value-too-long', { path: '/comment', limit: 500 } );
			assert.strictEqual( response.body.message, 'The input value is too long' );
		} );

		it( 'invalid edit tag', async () => {
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.withJsonBodyParam( 'tags', [ 'invalid tag' ] )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/tags/0' } );
		} );

		it( 'invalid bot flag', async () => {
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.withJsonBodyParam( 'bot', 'should be a boolean' )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/bot' } );
		} );

		it( 'invalid comment', async () => {
			const response = await newAddItemStatementRequestBuilder( testItemId, testStatement )
				.withJsonBodyParam( 'comment', 123 )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/comment' } );
		} );

		it( 'invalid statement field', async () => {
			const invalidStatement = { property: { id: [ 'P1' ] }, value: { type: 'novalue' } };
			const response = await newAddItemStatementRequestBuilder( testItemId, invalidStatement )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/statement/property/id' } );
			assert.include( response.body.message, '/statement/property/id' );
		} );

		it( 'missing top-level field', async () => {
			const response = await newAddItemStatementRequestBuilder( testItemId, {} )
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '/', field: 'statement' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'missing statement field', async () => {
			const invalidStatement = structuredClone( testStatement );
			delete invalidStatement.property.id;

			const response = await newAddItemStatementRequestBuilder( testItemId, invalidStatement )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'missing-field', { path: '/statement/property', field: 'id' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( 'invalid statement type: string', async () => {
			const invalidStatement = 'statement-not-string';
			const response = await newAddItemStatementRequestBuilder( testItemId, invalidStatement )
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-value' );
			assert.deepEqual( response.body.context, { path: '/statement' } );
		} );

		it( 'invalid statement type: array', async () => {
			const invalidStatement = [ 'statement-not-an-array' ];

			const response = await newAddItemStatementRequestBuilder( testItemId, invalidStatement )
				.assertInvalidRequest()
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/statement' } );
		} );
	} );

	describe( '404 error response', () => {
		it( 'item not found', async () => {
			const itemId = 'Q999999';
			const response = await newAddItemStatementRequestBuilder( itemId, testStatement )
				.assertValidRequest()
				.makeRequest();

			assertValidError( response, 404, 'resource-not-found', { resource_type: 'item' } );
			assert.strictEqual( response.body.message, 'The requested resource does not exist' );
		} );
	} );

	describe( '409 error response', () => {
		it( 'item is a redirect', async () => {
			const redirectTarget = testItemId;
			const redirectSource = await entityHelper.createRedirectForItem( redirectTarget );

			const response = await newAddItemStatementRequestBuilder( redirectSource, testStatement ).makeRequest();

			assertValidError( response, 409, 'redirected-item', { redirect_target: redirectTarget } );
			assert.include( response.body.message, redirectSource );
			assert.include( response.body.message, redirectTarget );
		} );
	} );
} );
