import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { interceptCommonsSearch, interceptFormatValue, interceptSaveEntity } from '../../support/apiMockHelpers';

describe( 'wbui2025 string datatypes (tabular-data and geo-shape)', () => {
	context( 'mobile view - tabular-data datatype', () => {
		let propertyName: string;
		let propertyId: string;
		let itemId: string;

		before( () => {
			propertyName = Util.getTestString( 'tabular-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'tabular-data' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				const statementData = {
					claims: [ {
						mainsnak: {
							snaktype: 'value',
							property: newPropertyId,
							datavalue: {
								value: 'Data:Ncei.noaa.gov/weather/New_York_City.tab',
								type: 'string',
							},
							datatype: 'tabular-data',
						},
						type: 'statement',
						rank: 'normal',
					} ],
				};
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'tabular-item' ),
					data: statementData,
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
				} );
			} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it( 'displays tabular-data statement and supports full editing workflow', () => {
		// Intercept the API for commons.
			interceptCommonsSearch( {
				results: [
					{
						ns: 486,
						title: 'Data:Weather_data.tab',
						pageid: 11111,
						size: 5000,
						wordcount: 400,
						snippet: 'Weather data table',
						timestamp: '2025-01-01T00:00:00Z',
					},
					{
						ns: 486,
						title: 'Data:Ncei.noaa.gov/weather/New_York_City.tab',
						pageid: 22222,
						size: 3500,
						wordcount: 300,
						snippet: 'NYC weather data',
						timestamp: '2025-01-01T00:00:00Z',
					},
					{
						ns: 486,
						title: 'Data:Weather_stations.tab',
						pageid: 33333,
						size: 2800,
						wordcount: 250,
						snippet: 'Weather stations data',
						timestamp: '2025-01-01T00:00:00Z',
					},
				],
			} );

			// Intercept wbformatvalue API.
			interceptFormatValue();

			// Intercept POST request.
			interceptSaveEntity( {
				itemId,
				propertyId,
				datatype: 'tabular-data',
				statements: [
					{
						value: 'Data:Weather_data.tab',
						hash: 'testtabulardata123',
						statementId: 'aaa111-bbbb-2222-cccc-333333333333',
					},
					{
						value: 'Data:Weather_stations.tab',
						hash: 'teesttabulardata123',
						statementId: 'bbb222-cccc-3333-dddd-444444444444',
					},
				],
			} );

			const itemViewPage = new ItemViewPage( itemId );
			itemViewPage.open().statementsSection();
			checkA11y( ItemViewPage.STATEMENTS );

			itemViewPage.editLinks().first().should( 'exist' ).should( 'be.visible' );
			itemViewPage.editLinks().first().click();

			const editFormPage = new EditStatementFormPage();
			editFormPage.formHeading().should( 'exist' );
			editFormPage.propertyName().should( 'have.text', propertyName );

			editFormPage.lookupComponent()
				.should( 'exist' ).should( 'be.visible' );

			editFormPage.lookupInput()
				.should( 'have.value', 'Data:Ncei.noaa.gov/weather/New_York_City.tab' );

			editFormPage.lookupInput().clear();
			editFormPage.lookupInput().type( 'Weather' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu().should( 'exist' );

			editFormPage.menuItems().eq( 0 ).click();
			editFormPage.lookupInput().should( 'have.value', 'Data:Weather_data.tab' );

			editFormPage.addValueButtons().first().click();
			editFormPage.valueForms().should( 'have.length', 2 );
			editFormPage.valueForms()
				.last()
				.find( editFormPage.getLookupComponentSelector() )
				.should( 'exist' );

			editFormPage.valueForms()
				.last()
				.find( editFormPage.getLookupInputSelector() )
				.type( 'Weather' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu().should( 'exist' );

			editFormPage.menuItems().eq( 2 ).click();
			editFormPage.valueForms()
				.last()
				.find( editFormPage.getLookupInputSelector() )
				.should( 'have.value', 'Data:Weather_stations.tab' );

			editFormPage.addQualifierButton().should( 'exist' );
			editFormPage.rankSelect().should( 'exist' );
			editFormPage.publishButton().click();

			cy.wait( '@saveStatement' );

			editFormPage.formHeading().should( 'not.exist' );
		} );
	} );

	context( 'mobile view - geo-shape datatype', () => {
		let propertyName: string;
		let propertyId: string;
		let itemId: string;

		before( () => {
			propertyName = Util.getTestString( 'geo-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'geo-shape' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				const statementData = {
					claims: [ {
						mainsnak: {
							snaktype: 'value',
							property: newPropertyId,
							datavalue: {
								value: 'Data:New York Central Railroad.map',
								type: 'string',
							},
							datatype: 'geo-shape',
						},
						type: 'statement',
						rank: 'normal',
					} ],
				};
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'geo-item' ),
					data: statementData,
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
				} );
			} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it( 'displays geo-shape statement and supports full editing workflow', () => {
		// Intercept the API for commons
			interceptCommonsSearch( {
				totalhits: 132,
				hasContinue: true,
				results: [
					{
						ns: 486,
						title: 'Data:Hamburg.map',
						pageid: 80473521,
						size: 4101,
						wordcount: 623,
						snippet: 'Hamburg city map data',
						timestamp: '2019-07-17T18:11:20Z',
					},
					{
						ns: 486,
						title: 'Data:Protected areas/Germany/HH/Naturschutzgebiet Stapelfelder Moor (Hamburg).map',
						pageid: 166797166,
						size: 2255,
						wordcount: 190,
						snippet: 'Hamburg protected area',
						timestamp: '2025-06-04T12:42:07Z',
					},
				],
			} );

			// Intercept wbformatvalue API
			interceptFormatValue();

			// Intercept the POST request
			interceptSaveEntity( {
				itemId,
				propertyId,
				datatype: 'geo-shape',
				statements: [
					{
						value: 'Data:Hamburg.map',
						hash: 'xyz789geoshape012',
						statementId: 'ccc333-dddd-4444-eeee-555555555555',
					},
					{
						value: 'Data:Protected areas/Germany/HH/Naturschutzgebiet Stapelfelder Moor (Hamburg).map',
						hash: 'uvw345geoshape678',
						statementId: 'ddd444-eeee-5555-ffff-666666666666',
					},
				],
				lastrevid: 12346,
			} );

			const itemViewPage = new ItemViewPage( itemId );
			itemViewPage.open().statementsSection();

			checkA11y( ItemViewPage.STATEMENTS );

			itemViewPage.editLinks().first().should( 'exist' ).should( 'be.visible' );
			itemViewPage.editLinks().first().click();

			const editFormPage = new EditStatementFormPage();

			editFormPage.formHeading().should( 'exist' );
			editFormPage.propertyName().should( 'have.text', propertyName );

			editFormPage.lookupComponent()
				.should( 'exist' );

			editFormPage.lookupInput()
				.should( 'have.value', 'Data:New York Central Railroad.map' );

			editFormPage.lookupInput().clear();
			editFormPage.lookupInput().type( 'Hamburg' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu()
				.should( 'exist' )
				.should( 'be.visible' );

			editFormPage.menuItems().eq( 0 ).click();
			editFormPage.lookupInput().should( 'have.value', 'Data:Hamburg.map' );

			editFormPage.addValueButtons().first().click();
			editFormPage.valueForms().should( 'have.length', 2 );
			editFormPage.valueForms()
				.last()
				.find( editFormPage.getLookupComponentSelector() )
				.should( 'exist' );

			editFormPage.valueForms()
				.last()
				.find( editFormPage.getLookupInputSelector() )
				.type( 'Hamburg' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu().should( 'exist' );

			editFormPage.menuItems().eq( 1 ).click();
			editFormPage.valueForms()
				.last()
				.find( editFormPage.getLookupInputSelector() )
				.should( 'have.value', 'Data:Protected areas/Germany/HH/Naturschutzgebiet Stapelfelder Moor (Hamburg).map' );

			editFormPage.addQualifierButton().should( 'exist' );
			editFormPage.rankSelect().should( 'exist' );
			editFormPage.publishButton().click();

			cy.wait( '@saveStatement' );

			editFormPage.formHeading().should( 'not.exist' );
		} );
	} );
} );
