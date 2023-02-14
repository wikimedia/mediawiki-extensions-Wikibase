'use strict';

const {
	createUniqueStringProperty,
	createItemWithStatements,
	newLegacyStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const {
	newPatchItemStatementRequestBuilder,
	newPatchStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const expect = require( 'chai' ).expect;

describe( 'validate PATCH endpoints against OpenAPI definition', () => {
	let testItemId;
	let testStatementId;
	let stringPropertyId;

	before( async function () {
		stringPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createItemWithStatements( [
			newLegacyStatementWithRandomStringValue( stringPropertyId )
		] );
		testItemId = createEntityResponse.entity.id;
		testStatementId = createEntityResponse.entity.claims[ stringPropertyId ][ 0 ].id;
	} );

	[
		( statementId, patch ) => newPatchItemStatementRequestBuilder( testItemId, statementId, patch ),
		newPatchStatementRequestBuilder
	].forEach( ( newPatchRequestBuilder ) => {
		describe( newPatchRequestBuilder().getRouteDescription(), () => {

			it( '200', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{ op: 'replace', path: '/value/content', value: 'I be patched!' }
					]
				).makeRequest();

				expect( response.status ).to.equal( 200 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '400 - invalid patch provided', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					{ invalid: 'patch document' }
				).makeRequest();

				expect( response.status ).to.equal( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 - statement does not exist', async () => {
				const response = await newPatchRequestBuilder(
					`${testItemId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
					[
						{ op: 'replace', path: '/value/content', value: 'no patchy :(' }
					]
				).makeRequest();

				expect( response.status ).to.equal( 404 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '409 - cannot apply patch', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{ op: 'replace', path: '/incorrect/path', value: 'no patchy :(' }
					]
				).makeRequest();

				expect( response.status ).to.equal( 409 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '412 - precondition failed', async () => {
				const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{ op: 'replace', path: '/value/content', value: 'no patchy :(' }
					]
				)
					.withHeader( 'If-Unmodified-Since', yesterday )
					.makeRequest();

				expect( response.status ).to.equal( 412 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '415 - unsupported media type', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{ op: 'replace', path: '/value/content', value: 'no patchy :(' }
					]
				)
					.withHeader( 'Content-Type', 'text/plain' )
					.makeRequest();

				expect( response.status ).to.equal( 415 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '422 - patch results in an invalid statement', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{
							op: 'replace',
							path: '/value/content',
							value: { invalid: [ 'datavalue', 'value', 'for a string datavalue' ] }
						}
					]
				).makeRequest();

				expect( response.status ).to.equal( 422 );
				expect( response ).to.satisfyApiSpec;
			} );

		} );

	} );

} );
