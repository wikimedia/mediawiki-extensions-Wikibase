'use strict';

const { assert, action, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const entityHelper = require( '../helpers/entityHelper' );
const { newCreatePropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
const { makeEtag } = require( '../helpers/httpHelper' );
const { getOrCreateBotUser } = require( '../helpers/testUsers' );

describe( newCreatePropertyRequestBuilder().getRouteDescription(), () => {

	describe( '201 success response ', () => {
		it( 'can create a minimal property', async () => {
			const property = { data_type: 'string' };
			const response = await newCreatePropertyRequestBuilder( property )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 201 );
			assert.deepEqual( response.body.data_type, property.data_type );

			const editMetadata = await entityHelper.getLatestEditMetadata( response.body.id );
			assert.header( response, 'etag', makeEtag( editMetadata.revid ) );
			assert.header( response, 'last-modified', editMetadata.timestamp );
		} );

		it( 'can create a property with all fields', async () => {
			const labels = { en: `instance of-${ utils.uniq() }` };
			const descriptions = { en: 'that class of which this subject is a particular example and member' };
			const aliases = { en: [ 'is a', 'type' ] };
			const data_type = 'string';

			const statementPropertyId = ( await entityHelper.createUniqueStringProperty() ).entity.id;
			const statementValue = '99 Bottles of Milk';
			const statements = {
				[ statementPropertyId ]: [ {
					property: { id: statementPropertyId },
					value: { type: 'value', content: statementValue }
				} ]
			};

			const response = await newCreatePropertyRequestBuilder( {
				data_type,
				labels,
				descriptions,
				aliases,
				statements
			} ).assertValidRequest().makeRequest();

			expect( response ).to.have.status( 201 );
			assert.deepEqual( response.body.labels, labels );
			assert.deepEqual( response.body.descriptions, descriptions );
			assert.deepEqual( response.body.aliases, aliases );
			assert.strictEqual( response.body.statements[ statementPropertyId ][ 0 ].value.content, statementValue );
			assert.deepEqual( response.body.data_type, 'string' );
		} );

		it( 'can create a property with edit metadata provided', async () => {
			const user = await getOrCreateBotUser();
			const tag = await action.makeTag( 'e2e test tag', 'Created during e2e test' );
			const editSummary = 'omg look i created a property';

			const response = await newCreatePropertyRequestBuilder( { data_type: 'string' } )
				.withJsonBodyParam( 'tags', [ tag ] )
				.withJsonBodyParam( 'bot', true )
				.withJsonBodyParam( 'comment', editSummary )
				.withUser( user )
				.assertValidRequest()
				.makeRequest();

			expect( response ).to.have.status( 201 );
			const editMetadata = await entityHelper.getLatestEditMetadata( response.body.id );
			assert.deepEqual( editMetadata.tags, [ tag ] );
			assert.property( editMetadata, 'bot' );
			assert.strictEqual(
				editMetadata.comment,
				`/* wbeditentity-create-property:0| */ ${editSummary}`
			);
			assert.strictEqual( editMetadata.user, user.username );
		} );
	} );
} );
