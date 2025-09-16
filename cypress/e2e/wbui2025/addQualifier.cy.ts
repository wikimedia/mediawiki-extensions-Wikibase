import { Util } from 'cypress-wikibase-api';

import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddQualifierFormPage } from '../../support/pageObjects/AddQualifierFormPage';
import { interceptCommonsSearch } from '../../support/apiMockHelpers';

describe( 'wbui2025 add qualifiers', () => {
	context( 'mobile view', () => {
		let itemViewPage: ItemViewPage;
		beforeEach( () => {
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
				.then( ( propertyId: string ) => {
					cy.wrap( propertyId ).as( 'propertyId' );
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
					cy.task( 'MwApi:CreateItem', { label: Util.getTestString( 'item' ), data: statementData } )
						.then( ( itemId: string ) => {
							itemViewPage = new ItemViewPage( itemId );
						} );
				} );
			cy.viewport( 375, 1280 );
		} );

		it( 'is possible to add a qualifier', () => {
			itemViewPage.open();
			itemViewPage.editLinks().first().click();
			const editStatementFormPage = new EditStatementFormPage();
			editStatementFormPage.addQualifierButton().click();
			const addQualifierFormPage = new AddQualifierFormPage();
			addQualifierFormPage.heading().should( 'have.text', 'add qualifier' );

			// Before a property is selected
			addQualifierFormPage.addButton().should( 'be.disabled' );
			addQualifierFormPage.snakValueInput().should( 'not.exist' );

			cy.get<string>( '@propertyId' ).then( ( propertyId ) => {
				addQualifierFormPage.setProperty( propertyId );
			} );
			const qualifierSnakValue = Util.getTestString( 'qualifierSnak' );
			addQualifierFormPage.setSnakValue( qualifierSnakValue );
			addQualifierFormPage.addButton().click();

			editStatementFormPage.valueForms().should( 'contain.text', qualifierSnakValue );
			editStatementFormPage.publishButton().click();
			itemViewPage.qualifiersSections().first().then( ( element ) => {
				itemViewPage.qualifiers( element ).should( 'contain.text', qualifierSnakValue );
			} );
		} );
	} );

	context( 'mobile view (wbui2025) - tabular-data qualifier', () => {
		let itemViewPage: ItemViewPage;
		let tabularPropertyId: string;

		beforeEach( () => {
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
				.then( ( propertyId: string ) => {
					cy.task( 'MwApi:CreateProperty', {
						label: Util.getTestString( 'tabular-qualifier-prop' ),
						data: { datatype: 'tabular-data' },
					} ).then( ( tabularPropId: string ) => {
						tabularPropertyId = tabularPropId;
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
						cy.task( 'MwApi:CreateItem', {
							label: Util.getTestString( 'item-tabular-qual' ),
							data: statementData,
						} ).then( ( itemId: string ) => {
							itemViewPage = new ItemViewPage( itemId );
						} );
					} );
				} );
			cy.viewport( 375, 1280 );
		} );

		it( 'can add a tabular-data qualifier with lookup', () => {
			interceptCommonsSearch( {
				results: [
					{
						ns: 486,
						title: 'Data:Example.tab',
						pageid: 123456,
						size: 1000,
						wordcount: 100,
						snippet: 'Example tabular data',
						timestamp: '2025-01-01T00:00:00Z',
					},
					{
						ns: 486,
						title: 'Data:Sample.tab',
						pageid: 123457,
						size: 1500,
						wordcount: 150,
						snippet: 'Sample tabular data',
						timestamp: '2025-01-01T00:00:00Z',
					},
				],
			} );

			itemViewPage.open();
			itemViewPage.editLinks().first().click();
			const editStatementFormPage = new EditStatementFormPage();
			editStatementFormPage.addQualifierButton().click();
			const addQualifierFormPage = new AddQualifierFormPage();

			addQualifierFormPage.setProperty( tabularPropertyId );

			addQualifierFormPage.snakValueLookup()
				.should( 'exist' );
			addQualifierFormPage.snakValueTextInput()
				.should( 'not.exist' );

			addQualifierFormPage.snakValueLookupInput()
				.type( 'Example' );

			cy.wait( '@commonsSearch' );

			addQualifierFormPage.snakValueMenuItems().first()
				.invoke( 'text' )
				.then( ( selectedText ) => {
					cy.wrap( selectedText.trim() ).as( 'qualifierValue' );
					addQualifierFormPage.snakValueMenuItems().first().click();
				} );

			addQualifierFormPage.addButton().click();

			cy.get( '@qualifierValue' ).then( ( qualifierValue ) => {
				editStatementFormPage.valueForms().should( 'contain.text', qualifierValue );
			} );
		} );
	} );

	context( 'mobile view (wbui2025) - geo-shape qualifier', () => {
		let itemViewPage: ItemViewPage;
		let geoShapePropertyId: string;

		beforeEach( () => {
			cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
				.then( ( propertyId: string ) => {
					cy.task( 'MwApi:CreateProperty', {
						label: Util.getTestString( 'geo-qualifier-prop' ),
						data: { datatype: 'geo-shape' },
					} ).then( ( geoPropId: string ) => {
						geoShapePropertyId = geoPropId;
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
						cy.task( 'MwApi:CreateItem', {
							label: Util.getTestString( 'item-geo-qual' ),
							data: statementData,
						} ).then( ( itemId: string ) => {
							itemViewPage = new ItemViewPage( itemId );
						} );
					} );
				} );
			cy.viewport( 375, 1280 );
		} );

		it( 'can add a geo-shape qualifier with lookup', () => {
			interceptCommonsSearch( {
				results: [
					{
						ns: 486,
						title: 'Data:Country_borders.map',
						pageid: 234567,
						size: 2000,
						wordcount: 200,
						snippet: 'Country borders map data',
						timestamp: '2025-01-01T00:00:00Z',
					},
					{
						ns: 486,
						title: 'Data:Countries.geojson',
						pageid: 234568,
						size: 2500,
						wordcount: 250,
						snippet: 'Countries geojson data',
						timestamp: '2025-01-01T00:00:00Z',
					},
				],
			} );

			itemViewPage.open();
			itemViewPage.editLinks().first().click();
			const editStatementFormPage = new EditStatementFormPage();
			editStatementFormPage.addQualifierButton().click();
			const addQualifierFormPage = new AddQualifierFormPage();

			addQualifierFormPage.setProperty( geoShapePropertyId );

			addQualifierFormPage.snakValueLookup()
				.should( 'exist' );
			addQualifierFormPage.snakValueTextInput()
				.should( 'not.exist' );

			addQualifierFormPage.snakValueLookupInput()
				.type( 'Country' );

			cy.wait( '@commonsSearch' );

			addQualifierFormPage.snakValueMenuItems().first()
				.invoke( 'text' )
				.then( ( selectedText ) => {
					cy.wrap( selectedText.trim() ).as( 'qualifierValue' );
					addQualifierFormPage.snakValueMenuItems().first().click();
				} );

			addQualifierFormPage.addButton().click();

			cy.get( '@qualifierValue' ).then( ( qualifierValue ) => {
				editStatementFormPage.valueForms().should( 'contain.text', qualifierValue );
			} );
		} );

		it( 'shows dropdown menu when typing in geo-shape qualifier lookup', () => {
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

			itemViewPage.open();
			itemViewPage.editLinks().first().click();
			const editStatementFormPage = new EditStatementFormPage();
			editStatementFormPage.addQualifierButton().click();
			const addQualifierFormPage = new AddQualifierFormPage();

			addQualifierFormPage.setProperty( geoShapePropertyId );

			addQualifierFormPage.snakValueLookupInput()
				.type( 'Hamburg' );

			cy.wait( '@commonsSearch' );
			addQualifierFormPage.menu().should( 'exist' );
		} );
	} );
} );
