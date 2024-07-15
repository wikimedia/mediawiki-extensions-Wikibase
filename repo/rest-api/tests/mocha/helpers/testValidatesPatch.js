'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( './chaiHelper' );
const { assertValidError } = require( './responseValidator' );

// eslint-disable-next-line mocha/no-exports
module.exports = function testValidatesPatch( newRequestBuilder ) {
	describe( 'validates the patch', () => {
		it( 'invalid patch', async () => {
			const response = await newRequestBuilder( { foo: 'this is not a valid JSON Patch' } )
				.assertInvalidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch' } );
			assert.include( response.body.message, '/patch' );
		} );

		it( 'missing top-level field', async () => {
			const response = await newRequestBuilder()
				.withEmptyJsonBody()
				.assertInvalidRequest()
				.makeRequest();

			expect( response ).to.have.status( 400 );
			assert.strictEqual( response.body.code, 'missing-field' );
			assert.deepEqual( response.body.context, { path: '/', field: 'patch' } );
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - missing 'op' field", async () => {
			const response = await newRequestBuilder( [ { path: '/a/b/c', value: 'test' } ] )
				.assertInvalidRequest().makeRequest();

			assertValidError(
				response,
				400,
				'missing-field',
				{ path: '/patch/0', field: 'op' }
			);
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - missing 'path' field", async () => {
			const response = await newRequestBuilder( [ { op: 'remove' } ] )
				.assertInvalidRequest().makeRequest();
			assertValidError(
				response,
				400,
				'missing-field',
				{ path: '/patch/0', field: 'path' }
			);
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - missing 'value' field", async () => {
			const response = await newRequestBuilder( [ { op: 'add', path: '/a/b/c' } ] )
				.makeRequest();

			assertValidError(
				response,
				400,
				'missing-field',
				{ path: '/patch/0', field: 'value' }
			);
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - missing 'from' field", async () => {
			const response = await newRequestBuilder( [ { op: 'move', path: '/a/b/c' } ] )
				.makeRequest();

			assertValidError(
				response,
				400,
				'missing-field',
				{ path: '/patch/0', field: 'from' }
			);
			assert.strictEqual( response.body.message, 'Required field missing' );
		} );

		it( "invalid patch - invalid 'op' field", async () => {
			const path = '/patch/0/op';
			const response = await newRequestBuilder( [ { op: 'foobar', path: '/a/b/c', value: 'test' } ] )
				.assertInvalidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: path } );
			assert.include( response.body.message, path );
		} );

		it( "invalid patch - 'op' is not a string", async () => {
			const invalidOperation = { op: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c', value: 'test' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch/0/op' } );
			assert.include( response.body.message, '/patch/0/op' );
		} );

		it( "invalid patch - 'path' is not a string", async () => {
			const invalidOperation = { op: 'add', path: { foo: [ 'bar' ], baz: 42 }, value: 'test' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.assertInvalidRequest().makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch/0/path' } );
			assert.include( response.body.message, '/patch/0/path' );
		} );

		it( "invalid patch - 'from' is not a string", async () => {
			const invalidOperation = { op: 'move', from: { foo: [ 'bar' ], baz: 42 }, path: '/a/b/c' };
			const response = await newRequestBuilder( [ invalidOperation ] )
				.makeRequest();

			assertValidError( response, 400, 'invalid-value', { path: '/patch/0/from' } );
			assert.include( response.body.message, '/patch/0/from' );
		} );
	} );
};
