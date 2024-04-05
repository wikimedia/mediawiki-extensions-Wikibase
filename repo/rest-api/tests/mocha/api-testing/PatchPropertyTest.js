'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const { assert, utils } = require( 'api-testing' );
const { newPatchPropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const entityHelper = require( '../helpers/entityHelper' );
const { makeEtag } = require( '../helpers/httpHelper' );
const testValidatesPatch = require( '../helpers/testValidatesPatch' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { formatPropertyEditSummary } = require( '../helpers/formatEditSummaries' );

describe( newPatchPropertyRequestBuilder().getRouteDescription(), () => {

	let testPropertyId;
	let originalLastModified;
	let originalRevisionId;
	let predicatePropertyId;
	const testEnglishLabel = `some-label-${utils.uniq()}`;

	before( async function () {
		testPropertyId = ( await entityHelper.createEntity( 'property', {
			datatype: 'string',
			labels: [ { language: 'en', value: testEnglishLabel } ],
			descriptions: [ { language: 'en', value: `some-description-${utils.uniq()}` } ],
			aliases: [ { language: 'fr', value: 'croissant' } ]
		} ) ).entity.id;

		const testPropertyCreationMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
		originalLastModified = new Date( testPropertyCreationMetadata.timestamp );
		originalRevisionId = testPropertyCreationMetadata.revid;

		predicatePropertyId = ( await entityHelper.createEntity( 'property', { datatype: 'string' } ) ).entity.id;

		// wait 1s before next test to ensure the last-modified timestamps are different
		await new Promise( ( resolve ) => {
			setTimeout( resolve, 1000 );
		} );
	} );

	describe( '200 OK', () => {

		it( 'can patch a property', async () => {
			const newLabel = `neues deutsches label ${utils.uniq()}`;
			const updatedDescription = `changed description ${utils.uniq()}`;
			const newStatementValue = 'new statement';
			const editSummary = 'I made a patch';
			const response = await newPatchPropertyRequestBuilder(
				testPropertyId,
				[
					{ op: 'add', path: '/labels/de', value: newLabel },
					{ op: 'replace', path: '/descriptions/en', value: updatedDescription },
					{ op: 'remove', path: '/aliases/fr' },
					{
						op: 'add',
						path: `/statements/${predicatePropertyId}`,
						value: [ {
							property: { id: predicatePropertyId },
							value: { type: 'value', content: newStatementValue }
						} ]
					}
				]
			).withJsonBodyParam( 'comment', editSummary ).makeRequest();

			expect( response ).to.have.status( 200 );
			assert.strictEqual( response.body.id, testPropertyId );
			assert.strictEqual( response.body.labels.de, newLabel );
			assert.strictEqual( response.body.descriptions.en, updatedDescription );
			assert.isEmpty( response.body.aliases );
			assert.strictEqual( response.body.statements[ predicatePropertyId ][ 0 ].value.content, newStatementValue );
			assert.match(
				response.body.statements[ predicatePropertyId ][ 0 ].id,
				new RegExp( `^${testPropertyId}\\$[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12}$`, 'i' )
			);
			assert.strictEqual( response.header[ 'content-type' ], 'application/json' );
			assert.isAbove( new Date( response.header[ 'last-modified' ] ), originalLastModified );
			assert.notStrictEqual( response.header.etag, makeEtag( originalRevisionId ) );

			const editMetadata = await entityHelper.getLatestEditMetadata( testPropertyId );
			assert.strictEqual(
				editMetadata.comment,
				formatPropertyEditSummary( 'update-languages-and-other-short', 'de, en, fr', editSummary )
			);
		} );

	} );

	describe( '400 Bad Request', () => {

		it( 'property ID is invalid', async () => {
			const propertyId = 'X123';
			const response = await newPatchPropertyRequestBuilder( propertyId, [] )
				.assertInvalidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-property-id', { 'property-id': propertyId } );
			assert.include( response.body.message, propertyId );
		} );

		testValidatesPatch( ( patch ) => newPatchPropertyRequestBuilder( testPropertyId, patch ) );

		it( 'comment too long', async () => {
			const comment = 'x'.repeat( 501 );
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', comment ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'comment-too-long' );
			assert.include( response.body.message, '500' );
		} );

		it( 'invalid edit tag', async () => {
			const invalidEditTag = 'invalid tag';
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', [ invalidEditTag ] ).assertValidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-edit-tag' );
			assert.include( response.body.message, invalidEditTag );
		} );

		it( 'invalid edit tag type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'tags', 'not an array' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'tags' );
			assert.strictEqual( response.body.expectedType, 'array' );
		} );

		it( 'invalid bot flag type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'bot', 'not boolean' ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'bot' );
			assert.strictEqual( response.body.expectedType, 'boolean' );
		} );

		it( 'invalid comment type', async () => {
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [] )
				.withJsonBodyParam( 'comment', 1234 ).assertInvalidRequest().makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'invalid-request-body' );
			assert.strictEqual( response.body.fieldName, 'comment' );
			assert.strictEqual( response.body.expectedType, 'string' );
		} );

	} );

	describe( '404 error response', () => {
		it( 'property not found', async () => {
			const propertyId = 'P99999';
			const response = await newPatchPropertyRequestBuilder( propertyId, [] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 404, 'property-not-found' );
			assert.include( response.body.message, propertyId );
		} );

	} );

	describe( '409 error response', () => {

		it( '"path" field target does not exist', async () => {
			const operation = { op: 'remove', path: '/path/does/not/exist' };

			const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'path', operation } );
			assert.include( response.body.message, operation.path );
		} );

		it( '"from" field target does not exist', async () => {
			const operation = { op: 'copy', from: '/path/does/not/exist', path: '/labels/en' };

			const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-target-not-found', { field: 'from', operation } );
			assert.include( response.body.message, operation.from );
		} );

		it( 'patch test condition failed', async () => {
			const operation = { op: 'test', path: '/labels/en', value: 'german-label' };
			const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
				.assertValidRequest().makeRequest();

			assertValidError( response, 409, 'patch-test-failed', { operation, 'actual-value': testEnglishLabel } );
			assert.include( response.body.message, operation.path );
			assert.include( response.body.message, JSON.stringify( operation.value ) );
			assert.include( response.body.message, testEnglishLabel );
		} );

	} );

} );
