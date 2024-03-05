'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createEntity, createLocalSitelink, getLocalSiteId } = require( '../helpers/entityHelper' );
const { newPatchSitelinksRequestBuilder } = require( '../helpers/RequestBuilderFactory' );
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
		const createItemResponse = await createEntity( 'item', {} );
		testItemId = createItemResponse.entity.id;

		await createLocalSitelink( testItemId, linkedArticle );
		siteId = await getLocalSiteId();
	} );

	it( '200 OK', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ makeReplaceExistingSitelinkOp() ]
		).makeRequest();

		expect( response ).to.have.status( 200 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400 - invalid patch', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ { invalid: 'patch' } ]
		).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '404 - item not found', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			'Q999999',
			[ makeReplaceExistingSitelinkOp() ]
		).makeRequest();

		expect( response ).to.have.status( 404 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409 - patch test failed', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ { op: 'test', path: `/${siteId}`, value: 'unexpected value!' } ]
		).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '412 - precondition failed', async () => {
		const yesterday = new Date( Date.now() - 24 * 60 * 60 * 1000 ).toUTCString();
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ makeReplaceExistingSitelinkOp() ]
		).withHeader( 'If-Unmodified-Since', yesterday ).makeRequest();

		expect( response ).to.have.status( 412 );
		expect( response ).to.satisfyApiSpec;
	} );

	// eslint-disable-next-line mocha/no-skipped-tests
	it.skip( '415 - unsupported media type', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ makeReplaceExistingSitelinkOp() ]
		).withHeader( 'Content-Type', 'text/plain' ).makeRequest();

		expect( response ).to.have.status( 415 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '422 - empty title', async () => {
		const response = await newPatchSitelinksRequestBuilder(
			testItemId,
			[ { op: 'replace', path: `/${siteId}`, value: { title: '' } } ]
		).makeRequest();

		expect( response ).to.have.status( 422 );
		expect( response ).to.satisfyApiSpec;
	} );

} );
