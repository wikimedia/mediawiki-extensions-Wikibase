'use strict';

const { expect } = require( '../helpers/chaiHelper' );
const {
	createUniqueStringProperty,
	createPropertyWithStatements,
	newLegacyStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const {
	newPatchPropertyStatementRequestBuilder,
	newPatchStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'validate PATCH endpoints for property statements against OpenAPI definition', () => {
	let testPropertyId;
	let testStatementId;
	let statementPropertyId;

	before( async function () {
		statementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		const createEntityResponse = await createPropertyWithStatements( [
			newLegacyStatementWithRandomStringValue( statementPropertyId )
		] );
		testPropertyId = createEntityResponse.entity.id;
		testStatementId = createEntityResponse.entity.claims[ statementPropertyId ][ 0 ].id;
	} );

	[
		( statementId, patch ) => newPatchPropertyStatementRequestBuilder( testPropertyId, statementId, patch ),
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

				expect( response ).to.have.status( 200 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '400 - invalid patch provided', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					{ invalid: 'patch document' }
				).makeRequest();

				expect( response ).to.have.status( 400 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '404 - statement does not exist', async () => {
				const response = await newPatchRequestBuilder(
					`${testPropertyId}$AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE`,
					[
						{ op: 'replace', path: '/value/content', value: 'no patchy :(' }
					]
				).makeRequest();

				expect( response ).to.have.status( 404 );
				expect( response ).to.satisfyApiSpec;
			} );

			it( '409 - cannot apply patch', async () => {
				const response = await newPatchRequestBuilder(
					testStatementId,
					[
						{ op: 'replace', path: '/incorrect/path', value: 'no patchy :(' }
					]
				).makeRequest();

				expect( response ).to.have.status( 409 );
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

				expect( response ).to.have.status( 412 );
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

				expect( response ).to.have.status( 415 );
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

				expect( response ).to.have.status( 422 );
				expect( response ).to.satisfyApiSpec;
			} );

		} );

	} );

} );
