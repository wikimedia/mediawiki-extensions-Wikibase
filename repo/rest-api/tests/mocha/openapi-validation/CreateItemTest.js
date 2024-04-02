'use strict';

const { utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const { createUniqueStringProperty, createWikiPage, getLocalSiteId } = require( '../helpers/entityHelper' );
const { newCreateItemRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newCreateItemRequestBuilder().getRouteDescription(), () => {
	let localSiteId;

	before( async () => {
		localSiteId = await getLocalSiteId();
	} );

	it( '201 - minimal item', async () => {
		const response = await newCreateItemRequestBuilder( {
			labels: { en: 'potato' }
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '201 - full item', async () => {
		const linkedArticle = utils.title( 'Potato' );
		await createWikiPage( linkedArticle );
		const statementProperty = ( await createUniqueStringProperty() ).entity.id;
		const response = await newCreateItemRequestBuilder( {
			labels: { en: 'potato' },
			descriptions: { en: 'root vegetable' },
			aliases: { en: [ 'spud', 'tater' ] },
			sitelinks: { [ localSiteId ]: { title: linkedArticle } },
			statements: {
				[ statementProperty ]: [ {
					property: { id: statementProperty },
					value: { type: 'novalue' }
				} ]
			}
		} ).makeRequest();

		expect( response ).to.have.status( 201 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '400', async () => {
		const response = await newCreateItemRequestBuilder( {
			unknownField: 'asdf'
		} ).makeRequest();

		expect( response ).to.have.status( 400 );
		expect( response ).to.satisfyApiSpec;
	} );

	it( '409', async () => {
		const linkedArticle = utils.title( 'Some article' );
		await createWikiPage( linkedArticle );
		await newCreateItemRequestBuilder( {
			labels: { en: 'existing linked item' },
			sitelinks: { [ localSiteId ]: { title: linkedArticle } }
		} ).makeRequest();

		const response = await newCreateItemRequestBuilder( {
			labels: { en: '409 test' },
			sitelinks: { [ localSiteId ]: { title: linkedArticle } }
		} ).makeRequest();

		expect( response ).to.have.status( 409 );
		expect( response ).to.satisfyApiSpec;
	} );
} );
