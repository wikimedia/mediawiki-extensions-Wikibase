import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { interceptCommonsSearch, interceptFormatValue, interceptSaveEntity } from '../../support/apiMockHelpers';
import { AddValueModal } from '../../support/pageObjects/AddValueModal';

describe( 'wbui2025 string datatypes (tabular-data, geo-shape, commonsMedia)', () => {
	context( 'mobile view - tabular-data datatype', () => {
		let propertyName: string;
		let propertyId: string;
		let itemId: string;
		let claimData: object;

		before( () => {
			// We want to test with a tabular-data property, but doing this will cause
			// Wikibase to try and validate that the values exist in Commons. Since we
			// are only testing the UI of the editStatement form, we can simply create
			// a string property in the backend, and reload the editStatement form with
			// claim data corresponding to a tabular-data property.
			propertyName = Util.getTestString( 'tabular-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'string' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				claimData = [ {
					mainsnak: {
						snaktype: 'value',
						property: newPropertyId,
						datavalue: {
							value: 'Data:Stubbed_Ncei.noaa.gov/weather/New_York_City.tab',
							type: 'string',
						},
						datatype: 'string',
					},
					type: 'statement',
					rank: 'normal',
				} ];
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'tabular-item' ),
					data: { claims: claimData },
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
					claimData[ 0 ].id = itemId + '$64fc215b-a4ba-4295-8adb-90a767191d4e';
				} );
				claimData[ 0 ].mainsnak.datatype = 'tabular-data';
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
						title: 'Data:Stubbed_Weather_data.tab',
						pageid: 11111,
						size: 5000,
						wordcount: 400,
						snippet: 'Weather data table',
						timestamp: '2025-01-01T00:00:00Z',
					},
					{
						ns: 486,
						title: 'Data:Stubbed_Ncei.noaa.gov/weather/New_York_City.tab',
						pageid: 22222,
						size: 3500,
						wordcount: 300,
						snippet: 'NYC weather data',
						timestamp: '2025-01-01T00:00:00Z',
					},
					{
						ns: 486,
						title: 'Data:Stubbed_Weather_stations.tab',
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
						value: 'Data:Stubbed_Weather_data.tab',
						hash: 'testtabulardata123',
						statementId: 'aaa111-bbbb-2222-cccc-333333333333',
					},
					{
						value: 'Data:Stubbed_Weather_stations.tab',
						hash: 'teesttabulardata123',
						statementId: 'bbb222-cccc-3333-dddd-444444444444',
					},
				],
			} );

			const itemViewPage = new ItemViewPage( itemId );
			itemViewPage.open().statementsSection();
			checkA11y( ItemViewPage.STATEMENTS );

			// We trigger the `wikibase.entityPage.entityLoaded` hook again here with modified
			// data so that we can test the editStatment UI for tabular-data without the backend
			// property needing to be tabular-data
			cy.window().then( ( win ) => {
				const claims = {};
				claims[ propertyId ] = claimData;
				win.mw.hook( 'wikibase.entityPage.entityLoaded' ).fire( { id: itemId, claims, type: 'item' } );
			} );

			itemViewPage.editLinks().first().should( 'exist' ).should( 'be.visible' );
			itemViewPage.editLinks().first().click();

			const editFormPage = new EditStatementFormPage();
			editFormPage.formHeading().should( 'exist' );
			editFormPage.propertyName().should( 'have.text', propertyName );

			editFormPage.lookupComponent()
				.should( 'exist' ).should( 'be.visible' );

			editFormPage.lookupInput()
				.should( 'have.value', 'Data:Stubbed_Ncei.noaa.gov/weather/New_York_City.tab' );

			editFormPage.lookupInput().clear();
			editFormPage.lookupInput().type( 'Weather' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu().should( 'exist' );

			editFormPage.menuItems().eq( 0 ).click();
			editFormPage.lookupInput().should( 'have.value', 'Data:Stubbed_Weather_data.tab' );
			editFormPage.addValueButtons().first().click();
			const addValueModal = new AddValueModal();
			addValueModal.modal().should( 'exist' );
			addValueModal.lookupInput().type( 'Weather' );
			cy.wait( '@commonsSearch' );
			addValueModal.menu().should( 'be.visible' );
			addValueModal.menuItems().eq( 2 ).click();
		} );
	} );

	context( 'mobile view - geo-shape datatype', () => {
		let propertyName: string;
		let propertyId: string;
		let itemId: string;
		let claimData: object;

		before( () => {
			// We want to test with a geo-shape property, but doing this will cause
			// Wikibase to try and validate that the values exist in Commons. Since we
			// are only testing the UI of the editStatement form, we can simply create
			// a string property in the backend, and reload the editStatement form with
			// claim data corresponding to a geo-shape property.
			propertyName = Util.getTestString( 'geo-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'string' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				claimData = [ {
					mainsnak: {
						snaktype: 'value',
						property: newPropertyId,
						datavalue: {
							value: 'Data:Stubbed_New York Central Railroad.map',
							type: 'string',
						},
						datatype: 'string',
					},
					type: 'statement',
					rank: 'normal',
				} ];
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'geo-item' ),
					data: { claims: claimData },
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
					claimData[ 0 ].id = itemId + '$17bf01aa-1407-45d1-ade6-62fc5a213f8e';
				} );
				claimData[ 0 ].mainsnak.datatype = 'geo-shape';
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
						title: 'Data:Stubbed_Hamburg.map',
						pageid: 80473521,
						size: 4101,
						wordcount: 623,
						snippet: 'Hamburg city map data',
						timestamp: '2019-07-17T18:11:20Z',
					},
					{
						ns: 486,
						title: 'Data:Stubbed_Protected areas/Germany/HH/Naturschutzgebiet Stapelfelder Moor (Hamburg).map',
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
						value: 'Data:Stubbed_Hamburg.map',
						hash: 'xyz789geoshape012',
						statementId: 'ccc333-dddd-4444-eeee-555555555555',
					},
					{
						value: 'Data:Stubbed_Protected areas/Germany/HH/Naturschutzgebiet Stapelfelder Moor (Hamburg).map',
						hash: 'uvw345geoshape678',
						statementId: 'ddd444-eeee-5555-ffff-666666666666',
					},
				],
				lastrevid: 12346,
			} );

			const itemViewPage = new ItemViewPage( itemId );
			itemViewPage.open().statementsSection();

			checkA11y( ItemViewPage.STATEMENTS );

			// We trigger the `wikibase.entityPage.entityLoaded` hook again here with modified
			// data so that we can test the editStatment UI for geo-shape data without the backend
			// property needing to be of geo-shape type
			cy.window().then( ( win ) => {
				const claims = {};
				claims[ propertyId ] = claimData;
				win.mw.hook( 'wikibase.entityPage.entityLoaded' ).fire( { id: itemId, claims, type: 'item' } );
			} );

			itemViewPage.editLinks().first().should( 'exist' ).should( 'be.visible' );
			itemViewPage.editLinks().first().click();

			const editFormPage = new EditStatementFormPage();

			editFormPage.formHeading().should( 'exist' );
			editFormPage.propertyName().should( 'have.text', propertyName );

			editFormPage.lookupComponent()
				.should( 'exist' );

			editFormPage.lookupInput()
				.should( 'have.value', 'Data:Stubbed_New York Central Railroad.map' );

			editFormPage.lookupInput().clear();
			editFormPage.lookupInput().type( 'Hamburg' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu()
				.should( 'exist' )
				.should( 'be.visible' );

			editFormPage.menuItems().eq( 0 ).click();
			editFormPage.lookupInput().should( 'have.value', 'Data:Stubbed_Hamburg.map' );
			editFormPage.addValueButtons().first().click();

			const addValueModal = new AddValueModal();
			addValueModal.modal().should( 'exist' );
			addValueModal.lookupInput().type( 'Hamburg' );
			cy.wait( '@commonsSearch' );
			addValueModal.menu().should( 'be.visible' );
			addValueModal.menuItems().eq( 1 ).click();

		} );
	} );

	context( 'mobile view - commonsMedia datatype', () => {
		let propertyName: string;
		let propertyId: string;
		let itemId: string;
		let claimData: object;

		before( () => {
			// We want to test with a commonsMedia property, but doing this will cause
			// Wikibase to try and validate that the values exist in Commons. Since we
			// are only testing the UI of the editStatement form, we can simply create
			// a string property in the backend, and reload the editStatement form with
			// claim data corresponding to a commonsMedia property.
			propertyName = Util.getTestString( 'commonsMedia-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'string' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				claimData = [ {
					mainsnak: {
						snaktype: 'value',
						property: newPropertyId,
						datavalue: {
							value: 'Stubbed Test.gif',
							type: 'string',
						},
						datatype: 'string',
					},
					type: 'statement',
					rank: 'normal',
				} ];
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'commonsMedia-item' ),
					data: { claims: claimData },
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
					claimData[ 0 ].id = itemId + '$17bf01aa-1407-45d1-ade6-62fc5a213f8e';
				} );
				claimData[ 0 ].mainsnak.datatype = 'commonsMedia';
			} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it( 'displays commonsMedia statement and supports full editing workflow', () => {
			// Intercept the API for commons
			interceptCommonsSearch( {
				totalhits: 239292,
				hasContinue: true,
				results: [
					{
						ns: 486,
						title: 'Stubbed PNG Test.png',
						pageid: 56855503,
						size: 98,
						wordcount: 48,
						snippet: 'Test',
						timestamp: '2026-01-04T23:59:41Z',
					},
					{
						ns: 486,
						title: 'Stubbed JPG Test.jpg',
						pageid: 56856129,
						size: 143,
						wordcount: 61,
						snippet: 'Test',
						timestamp: '2025-11-09T23:36:59Z',
					},
				],
			} );

			// Intercept wbformatvalue API
			interceptFormatValue();

			// Intercept the POST request
			interceptSaveEntity( {
				itemId,
				propertyId,
				datatype: 'commonsMedia',
				statements: [
					{
						value: 'Stubbed PNG Test.png',
						hash: 'testCommonsMedia1',
						statementId: 'aaaa1111-bb22-cc33-dd44-eeeeee555555',
					},
					{
						value: 'Stubbed JPG Test.jpg',
						hash: 'testCommonsMedia2',
						statementId: 'ffff6666-gg77-hh88-ii99-jjjjjj000000',
					},
				],
				lastrevid: 12346,
			} );

			const itemViewPage = new ItemViewPage( itemId );
			itemViewPage.open().statementsSection();

			checkA11y( ItemViewPage.STATEMENTS );

			// We trigger the `wikibase.entityPage.entityLoaded` hook again here with modified
			// data so that we can test the editStatment UI for commonsMedia data without the backend
			// property needing to be of commonsMedia type
			cy.window().then( ( win ) => {
				const claims = {};
				claims[ propertyId ] = claimData;
				win.mw.hook( 'wikibase.entityPage.entityLoaded' ).fire( { id: itemId, claims, type: 'item' } );
			} );

			itemViewPage.editLinks().first().should( 'exist' ).should( 'be.visible' );
			itemViewPage.editLinks().first().click();

			const editFormPage = new EditStatementFormPage();

			editFormPage.formHeading().should( 'exist' );
			editFormPage.propertyName().should( 'have.text', propertyName );

			editFormPage.lookupComponent()
				.should( 'exist' ).should( 'be.visible' );

			editFormPage.lookupInput()
				.should( 'have.value', 'Stubbed Test.gif' );

			editFormPage.lookupInput().clear();
			editFormPage.lookupInput().type( 'Test' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu()
				.should( 'exist' )
				.should( 'be.visible' );
			editFormPage.menuItemThumbnails()
				.should( 'exist' )
				.should( 'be.visible' );

			editFormPage.menuItems().eq( 0 ).click();
			editFormPage.lookupInput().should( 'have.value', 'Stubbed PNG Test.png' );

			editFormPage.addValueButtons().first().click();
			const addValueModal = new AddValueModal();
			addValueModal.modal().should( 'exist' );
			addValueModal.lookupInput().type( 'Test' );
			cy.wait( '@commonsSearch' );
			addValueModal.menu().should( 'be.visible' );
			addValueModal.menuItems().eq( 1 ).click();

			addValueModal.lookupInput().should( 'have.value', 'Stubbed JPG Test.jpg' );
			addValueModal.confirmButton().click();
			editFormPage.publishButton().click();

			cy.wait( '@saveStatement' );

			editFormPage.formHeading().should( 'not.exist' );
		} );
	} );
} );
