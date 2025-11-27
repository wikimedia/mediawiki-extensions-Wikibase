import { Util } from 'cypress-wikibase-api';

import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddReferenceFormPage } from '../../support/pageObjects/AddReferenceFormPage';
import { ValueForm } from '../../support/pageObjects/ValueForm';
import { interceptCommonsSearch, interceptFormatValue, interceptSaveEntity } from '../../support/apiMockHelpers.js';
import { LoginPage } from '../../support/pageObjects/LoginPage';

describe( 'wbui2025 add reference', () => {
	context( 'mobile view', () => {
		let itemViewPage: ItemViewPage;
		let itemLabel: string;
		let tabularDataPropertyId: string;
		let itemPropertyId: string;
		let stringPropertyId: string;
		const tabularDataItem: string = 'Data:Stubbed_Ncei.noaa.gov/weather/New_York_City.tab';

		beforeEach( () => {
			const loginPage = new LoginPage();
			cy.task(
				'MwApi:CreateUser',
				{ usernamePrefix: 'mextest' },
			).then( ( { username, password } ) => {
				loginPage.login( username, password );
			} );
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'tabular-data' } )
				.then( ( propertyId: string ) => {
					tabularDataPropertyId = propertyId;
				} );
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'wikibase-item' } )
				.then( ( propertyId: string ) => {
					itemPropertyId = propertyId;
				} );
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
				.then( ( propertyId: string ) => {
					stringPropertyId = propertyId;
					const statementData = {
						claims: [ {
							mainsnak: {
								snaktype: 'value',
								property: propertyId,
								datavalue: {
									value: 'example string value',
									type: 'string',
								},
							},
							type: 'statement',
							rank: 'normal',
						} ],
					};
					itemLabel = Util.getTestString( 'item' );
					cy.task( 'MwApi:CreateItem', { label: itemLabel, data: statementData } )
						.then( ( itemId: string ) => {
							itemViewPage = new ItemViewPage( itemId );
						} );
				} );
			cy.viewport( 375, 1280 );

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
		} );

		it( 'is possible to add references for string and lookup datatypes', () => {
			itemViewPage.open();
			itemViewPage.editLinks().first().click();
			const editStatementFormPage = new EditStatementFormPage();

			/*
			 * Add string reference
			 */
			editStatementFormPage.addReferenceButton().click();
			const addReferenceFormPage = new AddReferenceFormPage();
			addReferenceFormPage.heading().should( 'have.text', 'add reference' );

			// Before a property is selected
			addReferenceFormPage.addButton().should( 'be.disabled' );
			addReferenceFormPage.snakValueInput().should( 'not.exist' );

			addReferenceFormPage.setProperty( stringPropertyId );
			const referenceSnakValue = Util.getTestString( 'referenceSnak' );
			addReferenceFormPage.setSnakValue( referenceSnakValue );
			addReferenceFormPage.addButton().click();

			editStatementFormPage.references().first().then( ( element ) => {
				const valueForm = new ValueForm( element );
				valueForm.textInput().should( 'have.value', referenceSnakValue );
			} );

			/*
			 * Add item reference
			 */
			editStatementFormPage.addReferenceButton().click();
			addReferenceFormPage.heading().should( 'have.text', 'add reference' );

			// Before a property is selected
			addReferenceFormPage.addButton().should( 'be.disabled' );
			addReferenceFormPage.snakValueInput().should( 'not.exist' );

			addReferenceFormPage.setProperty( itemPropertyId );
			addReferenceFormPage.setSnakValue( itemLabel );
			addReferenceFormPage.menuItems().first().click();
			addReferenceFormPage.addButton().click();

			editStatementFormPage.references().last().then( ( element ) => {
				const valueForm = new ValueForm( element );
				valueForm.textInput().should( 'have.value', itemLabel );
			} );

			/*
 			 * Add tabular-data reference
 			 */
			editStatementFormPage.addReferenceButton().click();
			addReferenceFormPage.heading().should( 'have.text', 'add reference' );

			// Before a property is selected
			addReferenceFormPage.addButton().should( 'be.disabled' );
			addReferenceFormPage.snakValueInput().should( 'not.exist' );

			addReferenceFormPage.setProperty( tabularDataPropertyId );
			addReferenceFormPage.setSnakValue( tabularDataItem );
			addReferenceFormPage.menuItems().eq( 1 ).click();
			addReferenceFormPage.addButton().click();

			editStatementFormPage.references().last().then( ( element ) => {
				const valueForm = new ValueForm( element );
				valueForm.textInput().should( 'have.value', tabularDataItem );
			} );

			// Intercept wbformatvalue API
			interceptFormatValue( { Q5150: itemLabel } );
			const stringReferenceSnaks = {};
			stringReferenceSnaks[ stringPropertyId ] = [
				{
					snaktype: 'value',
					property: stringPropertyId,
					hash: 'refsnakhash1',
					datatype: 'string',
					datavalue: {
						value: referenceSnakValue,
						type: 'string',
					},
				},
			];
			const itemReferenceSnaks = {};
			itemReferenceSnaks[ itemPropertyId ] = [
				{
					snaktype: 'value',
					property: itemPropertyId,
					datatype: 'wikibase-item',
					hash: 'refsnakhash2',
					datavalue: {
						value: {
							'entity-type': 'item',
							'numeric-id': 5150,
							id: 'Q5150',
						},
						type: 'wikibase-entityid',
					},
				},
			];
			const tabularReferenceSnaks = {};
			tabularReferenceSnaks[ tabularDataPropertyId ] = [
				{
					snaktype: 'value',
					property: tabularDataPropertyId,
					datatype: 'tabular-data',
					hash: 'refsnakhash3',
					datavalue: {
						value: 'Data:Stubbed_Ncei.noaa.gov/weather/New_York_City.tab',
						type: 'string',
					},
				},
			];
			// Intercept the POST request
			interceptSaveEntity( {
				itemId: itemViewPage.getItemId(),
				propertyId: stringPropertyId,
				datatype: 'string',
				statements: [
					{
						statementId: '5284FB1A-4E4E-4B1D-B710-951F012D9CF4',
						value: 'example string value',
						references: [
							{
								'snaks-order': [ stringPropertyId ],
								snaks: stringReferenceSnaks,
							}, {
								'snaks-order': [ itemPropertyId ],
								snaks: itemReferenceSnaks,
							},
							{
								'snaks-order': [ tabularDataPropertyId ],
								snaks: tabularReferenceSnaks,
							},
						],
						'qualifiers-order': [],
						qualifiers: {},
						type: 'statement',
						rank: 'normal',
					},
				],
				lastrevid: 12346,
			} );

			editStatementFormPage.publishButton().click();
			editStatementFormPage.form().should( 'not.exist' );

			itemViewPage.referencesSections().first().then( ( element ) => {
				itemViewPage.referencesAccordion( element ).click();
				itemViewPage.references( element ).first().should( 'contain.text', referenceSnakValue );
				itemViewPage.references( element ).eq( 1 ).should( 'contain.text', itemLabel );
				itemViewPage.references( element ).last().should( 'contain.text', tabularDataItem );
			} );
		} );
	} );
} );
