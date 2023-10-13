'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( './chaiHelper' );

function assertValid400Response( response, responseBodyErrorCode, context = null ) {
	expect( response ).to.have.status( 400 );
	assert.header( response, 'Content-Language', 'en' );
	assert.strictEqual( response.body.code, responseBodyErrorCode );
	if ( context === null ) {
		assert.notProperty( response.body, 'context' );
	} else {
		assert.deepStrictEqual( response.body.context, context );
	}
}

// eslint-disable-next-line mocha/no-exports
module.exports = function testValidatesPatch( newRequestBuilder ) {
	describe( 'validates the patch', () => {
		it( 'invalid patch', async () => {
			const invalidPatch = { foo: 'this is not a valid JSON Patch' };
			const response = await newRequestBuilder( invalidPatch )
				.assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-patch' );
		} );

		it( "invalid patch - missing 'op' field", async () => {
			const invalidOperation = { path: '/a/b/c', value: 'test' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValid400Response(
				response,
				'missing-json-patch-field',
				{ operation: invalidOperation, field: 'op' }
			);
			assert.include( response.body.message, "'op'" );
		} );

		it( "invalid patch - missing 'path' field", async () => {
			const invalidOperation = { op: 'remove' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();
			assertValid400Response(
				response,
				'missing-json-patch-field',
				{ operation: invalidOperation, field: 'path' }
			);
			assert.include( response.body.message, "'path'" );
		} );

		it( "invalid patch - missing 'value' field", async () => {
			const invalidOperation = { op: 'add', path: '/a/b/c' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.makeRequest();

			assertValid400Response(
				response,
				'missing-json-patch-field',
				{ operation: invalidOperation, field: 'value' }
			);
			assert.include( response.body.message, "'value'" );
		} );

		it( "invalid patch - missing 'from' field", async () => {
			const invalidOperation = { op: 'move', path: '/a/b/c' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.makeRequest();

			assertValid400Response(
				response,
				'missing-json-patch-field',
				{ operation: invalidOperation, field: 'from' }
			);
			assert.include( response.body.message, "'from'" );
		} );

		it( "invalid patch - invalid 'op' field", async () => {
			const invalidOperation = { op: 'foobar', path: '/a/b/c', value: 'test' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValid400Response( response, 'invalid-patch-operation', { operation: invalidOperation } );
			assert.include( response.body.message, "'foobar'" );
		} );

		it( "invalid patch - 'op' is not a string", async () => {
			const invalidOperation = { op: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c', value: 'test' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValid400Response(
				response,
				'invalid-patch-field-type',
				{ operation: invalidOperation, field: 'op' }
			);
			assert.include( response.body.message, "'op'" );
		} );

		it( "invalid patch - 'path' is not a string", async () => {
			const invalidOperation = { op: 'add', path: { foo: [ 'bar' ], baz: 42 }, value: 'test' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValid400Response(
				response,
				'invalid-patch-field-type',
				{ operation: invalidOperation, field: 'path' }
			);
			assert.include( response.body.message, "'path'" );
		} );

		it( "invalid patch - 'from' is not a string", async () => {
			const invalidOperation = { op: 'move', from: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.makeRequest();

			assertValid400Response(
				response,
				'invalid-patch-field-type',
				{ operation: invalidOperation, field: 'from' }
			);
			assert.include( response.body.message, "'from'" );
		} );
	} );
};
