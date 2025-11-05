import { Util } from 'cypress-wikibase-api';

import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddReferenceFormPage } from '../../support/pageObjects/AddReferenceFormPage';
import { ValueForm } from '../../support/pageObjects/ValueForm';
import { interceptCommonsSearch } from '../../support/apiMockHelpers';
import { LoginPage } from '../../support/pageObjects/LoginPage';

describe( 'wbui2025 add reference', () => {
	context( 'mobile view', () => {
		let itemViewPage: ItemViewPage;
		let itemLabel: string;
		const tabularDataItem: string = 'Data:Ncei.noaa.gov/weather/New_York_City.tab';

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
					cy.wrap( propertyId ).as( 'tabularDataPropertyId' );
				} );
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'wikibase-item' } )
				.then( ( propertyId: string ) => {
					cy.wrap( propertyId ).as( 'itemPropertyId' );
				} );
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
				.then( ( propertyId: string ) => {
					cy.wrap( propertyId ).as( 'stringPropertyId' );
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

			cy.get( '@stringPropertyId' ).then( ( propertyId ) => {
				addReferenceFormPage.setProperty( propertyId );
			} );
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

			cy.get( '@itemPropertyId' ).then( ( propertyId ) => {
				addReferenceFormPage.setProperty( propertyId );
			} );
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

			cy.get( '@tabularDataPropertyId' ).then( ( propertyId ) => {
				addReferenceFormPage.setProperty( propertyId );
			} );
			addReferenceFormPage.setSnakValue( tabularDataItem );
			addReferenceFormPage.menuItems().eq( 1 ).click();
			addReferenceFormPage.addButton().click();

			editStatementFormPage.references().last().then( ( element ) => {
				const valueForm = new ValueForm( element );
				valueForm.textInput().should( 'have.value', tabularDataItem );
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
