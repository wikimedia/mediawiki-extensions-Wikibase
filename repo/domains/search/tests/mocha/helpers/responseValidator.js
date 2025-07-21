'use strict';

const { assert } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );

module.exports = {
	assertValidError( response, statusCode, responseBodyErrorCode, context = null ) {
		expect( response ).to.have.status( statusCode );
		assert.header( response, 'Content-Language', 'en' );
		assert.strictEqual( response.body.code, responseBodyErrorCode );
		if ( context === null ) {
			assert.notProperty( response.body, 'context' );
		} else {
			assert.deepStrictEqual( response.body.context, context );
		}
	}
};
