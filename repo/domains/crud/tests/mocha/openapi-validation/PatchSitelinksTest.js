'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../../../../../rest-api/tests/mocha/helpers/chaiHelper' );
const { createLocalSitelink, getLocalSiteId } = require( '../helpers/entityHelper' );
const {
	newPatchSitelinksRequestBuilder,
	newCreateItemRequestBuilder
} = require( '../helpers/RequestBuilderFactory' );
const { getAllowedBadges } = require( '../helpers/getAllowedBadges' );

describe( newPatchSitelinksRequestBuilder().getRouteDescription(), () => {

	let testItemId;
	let siteId;
	const linkedArticle = utils.title( 'test-title-' );
	const sitelink = { title: linkedArticle, badges: getAllowedBadges()[ 0 ] };

	function makeReplaceExistingSitelinkOp() {
		return {
			op: 'replace',
			path: `/${siteId}`,
			value: sitelink
		};
	}

	before( async () => {
		const createItemResponse = await newCreateItemRequestBuilder( {} ).makeRequest();
		testItemId = createItemResponse.body.id;

		await createLocalSitelink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();
	} );

	it( '200 OK', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ makeReplaceExistingSitelinkOp() ]
		).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ { invalid: 'patch' } ]
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '404 - item not found', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			'Q999999',
			[ makeReplaceExistingSitelinkOp() ]
		).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ { op: 'test', path: `/${siteId}`, value: 'unexpected value!' } ]
		).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ makeReplaceExistingSitelinkOp() ]
		).withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSchema;
	} );

	it( '422 - empty title', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ { op: 'replace', path: `/${siteId}`, value: { title: '' } } ]
		).makeRequest();

		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSchema;
	} );

} );
