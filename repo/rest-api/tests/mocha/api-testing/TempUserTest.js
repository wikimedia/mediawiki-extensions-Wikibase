'use strict';

const { describeWithTestData } = require( '../helpers/describeWithTestData' );
const { assert } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	getItemEditRequests,
	getPropertyEditRequests
} = require( '../helpers/happyPathRequestBuilders' );
const entityHelper = require( '../helpers/entityHelper' );

describeWithTestData( 'IP masking', ( itemRequestInputs, propertyRequestInputs, describeEachRouteWithReset ) => {
	function withTempUserConfig( newRequestBuilder, config ) {
		return newRequestBuilder().withHeader( 'X-Wikibase-Ci-Tempuser-Config', JSON.stringify( config ) );
	}

	const editRequests = [
		...getItemEditRequests( itemRequestInputs ),
		...getPropertyEditRequests( propertyRequestInputs )
	];
	describeEachRouteWithReset( editRequests, ( newRequestBuilder, requestInputs ) => {
		it( 'makes an edit as an IP user with tempUser disabled', async () => {
			const response = await withTempUserConfig( newRequestBuilder, { enabled: false } )
				.makeRequest();

			expect( response ).status.to.be.within( 200, 299 );
			const { user } = await entityHelper.getLatestEditMetadata( requestInputs.mainTestSubject );
			assert.match( user, /^\d+\.\d+\.\d+\.\d+$/ );
		} );

		it( 'makes an edit as a temp user with tempUser enabled', async () => {
			const tempUserPrefix = 'TempUserTest';
			const response = await withTempUserConfig(
				newRequestBuilder,
				{ enabled: true, genPattern: `${tempUserPrefix} $1` }
			).makeRequest();

			expect( response ).status.to.be.within( 200, 299 );
			const { user } = await entityHelper.getLatestEditMetadata( requestInputs.mainTestSubject );
			assert.include( user, tempUserPrefix );
		} );
	} );
} );
