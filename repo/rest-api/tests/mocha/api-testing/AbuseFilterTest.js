'use strict';

const { clientFactory, action, utils } = require( 'api-testing' );
const {
	newCreateItemRequestBuilder,
	newSetItemLabelRequestBuilder,
	newSetPropertyLabelRequestBuilder,
	newAddItemStatementRequestBuilder,
	newAddPropertyStatementRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { assertValidError } = require( '../helpers/responseValidator' );
const { createUniqueStringProperty } = require( '../helpers/entityHelper' );
const { requireExtensions } = require( '../../../../../tests/api-testing/utils' );
const config = require( 'api-testing/lib/config' )();

/**
 * AbuseFilter doesn't have an API to create filters. This is a very hacky way around the issue:
 * - get the edit token (a CSRF token salted for the AbuseFilter form)
 * - make a POST request that looks like it's coming from said form
 *
 * @param {string} description
 * @param {string} rules
 * @return {Promise<number>} the filter ID
 */
async function createAbuseFilter( description, rules ) {
	const rootClient = await action.root();
	const client = clientFactory.getHttpClient( rootClient );

	const abuseFilterFormRequest = await client.get( `${config.base_uri}index.php?title=Special:AbuseFilter/new` );
	const editToken = abuseFilterFormRequest.text
		.match( /value="[a-z0-9]+\+\\"/ )[ 0 ] // the token is in the value attribute of an input field and ends with +\
		.slice( 'value="'.length, -1 ); // remove parts that were matched that aren't part of the token

	const createFilterResponse = await client.post( `${config.base_uri}index.php` ).type( 'form' ).send( {
		title: 'Special:AbuseFilter/new',
		wpEditToken: editToken,
		wpFilterDescription: description,
		wpFilterRules: rules,
		wpFilterEnabled: 'true',
		wpFilterBuilder: 'other',
		wpFilterNotes: '',
		wpFilterWarnMessage: 'abusefilter-warning',
		wpFilterWarnMessageOther: 'abusefilter-warning',
		wpFilterActionDisallow: '',
		wpFilterDisallowMessage: 'abusefilter-disallowed',
		wpFilterDisallowMessageOther: 'abusefilter-disallowed',
		wpBlockAnonDuration: 'indefinite',
		wpBlockUserDuration: 'indefinite',
		wpFilterTags: ''
	} );

	return parseInt( new URL( createFilterResponse.headers.location ).searchParams.get( 'changedfilter' ) );
}

describe( 'Abuse Filter', () => {

	const filterTriggerWord = utils.title( 'ABUSE-FILTER-TRIGGER-' );
	const filterDescription = `Filter: ${filterTriggerWord}`;
	let filterId;
	let testItemId;
	let testPropertyId;

	before( async function () {
		await requireExtensions( [ 'AbuseFilter' ] ).call( this );

		filterId = await createAbuseFilter( filterDescription, `"${filterTriggerWord}" in new_wikitext` );
		testItemId = ( await newCreateItemRequestBuilder( {} ).makeRequest() ).body.id;
		testPropertyId = ( await createUniqueStringProperty() ).entity.id;
	} );

	[
		() => newCreateItemRequestBuilder( { labels: { en: filterTriggerWord } } ),
		() => newSetItemLabelRequestBuilder( testItemId, 'en', filterTriggerWord ),
		() => newSetPropertyLabelRequestBuilder( testPropertyId, 'en', filterTriggerWord ),
		() => newAddItemStatementRequestBuilder(
			testItemId,
			{ property: { id: testPropertyId }, value: { type: 'value', content: filterTriggerWord } }
		),
		() => newAddPropertyStatementRequestBuilder(
			testPropertyId,
			{ property: { id: testPropertyId }, value: { type: 'value', content: filterTriggerWord } }
		)
	].forEach( ( newRequestBuilder ) => {
		it( `${newRequestBuilder().getRouteDescription()} rejects edits matching an abuse filter`, async () => {
			const response = await newRequestBuilder().makeRequest();

			assertValidError( response, 403, 'permission-denied', {
				denial_reason: 'abuse-filter',
				denial_context: { filter_id: filterId, filter_description: filterDescription }
			} );
		} );
	} );

} );
