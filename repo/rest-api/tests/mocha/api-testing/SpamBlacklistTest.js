'use strict';

const { assertValidError } = require( '../helpers/responseValidator' );
const { action, utils } = require( 'api-testing' );
const entityHelper = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const { newPatchPropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( 'Spam blacklist', () => {
	let user;
	let testPropertyId;
	let propertyWithUrlDataType;

	before( async function () {
		await requireExtensions( [ 'SpamBlacklist' ] ).call( this );

		user = await action.root();
		await user.edit( 'MediaWiki:Spam-blacklist', { text: '.*spam.com.*' } );

		testPropertyId = ( await entityHelper.createEntity( 'property', {
			datatype: 'string',
			labels: [ { language: 'en', value: `some-label-${utils.uniq()}` } ]
		} ) ).entity.id;

		propertyWithUrlDataType = ( await entityHelper.createEntity( 'property', { datatype: 'url' } ) ).entity.id;
	} );

	it( 'should respond 403 Forbidden when the statement value contains blacklisted content', async () => {
		const operation = {
			op: 'add',
			path: '/statements',
			value: {
				[ propertyWithUrlDataType ]: [ {
					property: { id: propertyWithUrlDataType },
					value: { content: 'http://www.spam.com', type: 'value' }
				} ]
			}
		};

		const response = await newPatchPropertyRequestBuilder( testPropertyId, [ operation ] )
			.assertValidRequest().makeRequest();

		assertValidError(
			response,
			403,
			'permission-denied',
			{
				denial_reason: 'spamblacklist',
				denial_context: { spamblacklist: { matches: [ 'spam.com/' ] } }
			}
		);
	} );
} );
