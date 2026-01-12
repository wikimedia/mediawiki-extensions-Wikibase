import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { interceptCommonsSearch, interceptFormatValue, interceptSaveEntity } from '../../support/apiMockHelpers';
import { AddValueModal } from '../../support/pageObjects/AddValueModal';

function annotateTestFailures(): void {
	cy.on( 'fail', ( e ): never => {
		if ( e.message.includes( 'does not exist' ) ) {
			e.message = 'Wikibase reported an error that a Commons page used by the test does not exist.\n' +
				'If you currently don’t have a stable internet connection, you can bypass this error\n' +
				'by adding `define( "MW_QUIBBLE_CI", 1 )` to your LocalSettings.php file.\n' +
				'Otherwise, the test may need an update to change the pages being referenced.\n\n' +
				e.message;
		}
		throw e;
	} );
}

describe( 'wbui2025 string datatypes (tabular-data, geo-shape, commonsMedia)', () => {
	context( 'mobile view - tabular-data datatype', () => {
		let propertyName: string;
		let propertyId: string;
		let itemId: string;
		let claimData: object;

		before( () => {
			propertyName = Util.getTestString( 'tabular-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'tabular-data' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				claimData = [ {
					mainsnak: {
						snaktype: 'value',
						property: newPropertyId,
						datavalue: {
							value: 'Data:DateI18n.tab',
							type: 'string',
						},
						datatype: 'tabular-data',
					},
					type: 'statement',
					rank: 'normal',
				} ];
				annotateTestFailures();
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'tabular-item' ),
					data: { claims: claimData },
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
					claimData[ 0 ].id = itemId + '$64fc215b-a4ba-4295-8adb-90a767191d4e';
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
						title: 'Data:I18n/EditAt.tab',
						pageid: 11111,
						size: 5000,
						wordcount: 400,
						snippet: 'Weather data table',
						timestamp: '2025-01-01T00:00:00Z',
					},
					{
						ns: 486,
						title: 'Data:DateI18n.tab',
						pageid: 22222,
						size: 3500,
						wordcount: 300,
						snippet: 'NYC weather data',
						timestamp: '2025-01-01T00:00:00Z',
					},
					{
						ns: 486,
						title: 'Data:Artwork types.tab',
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
						value: 'Data:I18n/EditAt.tab',
						hash: 'testtabulardata123',
						statementId: 'aaa111-bbbb-2222-cccc-333333333333',
					},
					{
						value: 'Data:Artwork types.tab',
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
				.should( 'have.value', 'Data:DateI18n.tab' );

			editFormPage.lookupInput().clear();
			editFormPage.lookupInput().type( 'Weather' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu().should( 'exist' );

			editFormPage.menuItems().eq( 0 ).click();
			editFormPage.lookupInput().should( 'have.value', 'Data:I18n/EditAt.tab' );
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
			propertyName = Util.getTestString( 'geo-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'geo-shape' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				claimData = [ {
					mainsnak: {
						snaktype: 'value',
						property: newPropertyId,
						datavalue: {
							value: 'Data:Neighbourhoods/New York City.map',
							type: 'string',
						},
						datatype: 'geo-shape',
					},
					type: 'statement',
					rank: 'normal',
				} ];
				annotateTestFailures();
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'geo-item' ),
					data: { claims: claimData },
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
					claimData[ 0 ].id = itemId + '$17bf01aa-1407-45d1-ade6-62fc5a213f8e';
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
						title: 'Data:Rhein-Radweg Hochrhein.map',
						pageid: 80473521,
						size: 4101,
						wordcount: 623,
						snippet: 'Hamburg city map data',
						timestamp: '2019-07-17T18:11:20Z',
					},
					{
						ns: 486,
						title: 'Data:Rhein-Radweg Mittelrhein.map',
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
						value: 'Data:Rhein-Radweg Hochrhein.map',
						hash: 'xyz789geoshape012',
						statementId: 'ccc333-dddd-4444-eeee-555555555555',
					},
					{
						value: 'Data:Rhein-Radweg Mittelrhein.map',
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
				.should( 'have.value', 'Data:Neighbourhoods/New York City.map' );

			editFormPage.lookupInput().clear();
			editFormPage.lookupInput().type( 'Hamburg' );

			cy.wait( '@commonsSearch' );
			editFormPage.menu()
				.should( 'exist' )
				.should( 'be.visible' );

			editFormPage.menuItems().eq( 0 ).click();
			editFormPage.lookupInput().should( 'have.value', 'Data:Rhein-Radweg Hochrhein.map' );
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
			propertyName = Util.getTestString( 'commonsMedia-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName,
				data: { datatype: 'commonsMedia' },
			} ).then( ( newPropertyId: string ) => {
				propertyId = newPropertyId;
				claimData = [ {
					mainsnak: {
						snaktype: 'value',
						property: newPropertyId,
						datavalue: {
							value: 'Commons-logo.svg',
							type: 'string',
						},
						datatype: 'commonsMedia',
					},
					type: 'statement',
					rank: 'normal',
				} ];
				annotateTestFailures();
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'commonsMedia-item' ),
					data: { claims: claimData },
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
					claimData[ 0 ].id = itemId + '$17bf01aa-1407-45d1-ade6-62fc5a213f8e';
				} );
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
						title: 'Wikipedia-logo-v2.svg',
						pageid: 56855503,
						size: 98,
						wordcount: 48,
						snippet: 'Test',
						timestamp: '2026-01-04T23:59:41Z',
					},
					{
						ns: 486,
						title: 'Wikidata-logo.svg',
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
						value: 'Wikipedia-logo-v2.svg',
						hash: 'testCommonsMedia1',
						statementId: 'aaaa1111-bb22-cc33-dd44-eeeeee555555',
					},
					{
						value: 'Wikidata-logo.svg',
						hash: 'testCommonsMedia2',
						statementId: 'ffff6666-gg77-hh88-ii99-jjjjjj000000',
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
				.should( 'exist' ).should( 'be.visible' );

			editFormPage.lookupInput()
				.should( 'have.value', 'Commons-logo.svg' );

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
			editFormPage.lookupInput().should( 'have.value', 'Wikipedia-logo-v2.svg' );

			editFormPage.addValueButtons().first().click();
			const addValueModal = new AddValueModal();
			addValueModal.modal().should( 'exist' );
			addValueModal.lookupInput().type( 'Test' );
			cy.wait( '@commonsSearch' );
			addValueModal.menu().should( 'be.visible' );
			addValueModal.menuItems().eq( 1 ).click();

			addValueModal.lookupInput().should( 'have.value', 'Wikidata-logo.svg' );
			addValueModal.confirmButton().click();
			editFormPage.publishButton().click();

			cy.wait( '@saveStatement' );

			editFormPage.formHeading().should( 'not.exist' );
		} );
	} );
} );
