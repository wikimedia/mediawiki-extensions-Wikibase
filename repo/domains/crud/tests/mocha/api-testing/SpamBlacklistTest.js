'use strict';

const { assertValidError } = require( '../helpers/responseValidator' );
const { action, utils } = require( 'api-testing' );
const { requireExtensions } = require( '../../../../../../tests/api-testing/utils' );
const {
	newPatchPropertyRequestBuilder,
	newCreatePropertyRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );

describe( 'Spam blacklist', () => {
	let user;
	let testPropertyId;
	let propertyWithUrlDataType;

	before( async function () {
		await requireExtensions( [ 'SpamBlacklist' ] ).call( this );

		user = await action.root();
		await user.edit( 'MediaWiki:Spam-blacklist', { text: '.*spam.com.*' } );

		testPropertyId = ( await newCreatePropertyRequestBuilder( {
			data_type: 'string',
			labels: { en: `some-label-${utils.uniq()}` }
		} ).makeRequest() ).body.id;

		propertyWithUrlDataType = ( await newCreatePropertyRequestBuilder( { data_type: 'url' } ).makeRequest() ).body.id;
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
