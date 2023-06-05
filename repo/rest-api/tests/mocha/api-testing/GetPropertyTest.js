'use strict';

const { assert, utils } = require( 'api-testing' );
const { expect } = require( '../helpers/chaiHelper' );
const {
	createEntity,
	createUniqueStringProperty,
	newLegacyStatementWithRandomStringValue
} = require( '../helpers/entityHelper' );
const { newGetPropertyRequestBuilder } = require( '../helpers/RequestBuilderFactory' );

describe( newGetPropertyRequestBuilder().getRouteDescription(), () => {
	const germanLabel = 'a-German-label-' + utils.uniq();
	const englishLabel = 'an-English-label-' + utils.uniq();
	const englishDescription = 'an-English-description-' + utils.uniq();
	const testPropertyDataType = 'wikibase-item';
	let testPropertyId;
	let testStatementPropertyId;
	let testStatement;

	before( async () => {
		testStatementPropertyId = ( await createUniqueStringProperty() ).entity.id;
		testStatement = newLegacyStatementWithRandomStringValue( testStatementPropertyId );

		const createPropertyResponse = await createEntity( 'property', {
			datatype: testPropertyDataType,
			labels: {
				de: { language: 'de', value: germanLabel },
				en: { language: 'en', value: englishLabel }
			},
			descriptions: {
				en: { language: 'en', value: englishDescription }
			},
			claims: [ testStatement ]
		} );
		testPropertyId = createPropertyResponse.entity.id;
	} );

	it( 'can GET all property data', async () => {
		const response = await newGetPropertyRequestBuilder( testPropertyId )
			.assertValidRequest()
			.makeRequest();

		expect( response ).to.have.status( 200 );

		assert.strictEqual( response.body.id, testPropertyId );
		assert.strictEqual( response.body[ 'data-type' ], testPropertyDataType );
		assert.deepEqual( response.body.aliases, {} ); // expect {}, not []
		assert.deepEqual( response.body.labels, {
			de: germanLabel,
			en: englishLabel
		} );
		assert.deepEqual( response.body.descriptions, { en: englishDescription } );

		assert.strictEqual(
			response.body.statements[ testStatementPropertyId ][ 0 ].value.content,
			testStatement.mainsnak.datavalue.value
		);
	} );

} );
