import { Util } from 'cypress-wikibase-api';

import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddReferenceFormPage } from '../../support/pageObjects/AddReferenceFormPage';
import { ValueForm } from '../../support/pageObjects/ValueForm';

describe( 'wbui2025 add reference', () => {
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

		it( 'is possible to add a reference', () => {
			itemViewPage.open();
			itemViewPage.editLinks().first().click();
			const editStatementFormPage = new EditStatementFormPage();
			editStatementFormPage.addReferenceButton().click();
			const addReferenceFormPage = new AddReferenceFormPage();
			addReferenceFormPage.heading().should( 'have.text', 'add reference' );

			// Before a property is selected
			addReferenceFormPage.addButton().should( 'be.disabled' );
			addReferenceFormPage.snakValueInput().should( 'not.exist' );

			cy.get( '@propertyId' ).then( ( propertyId ) => {
				addReferenceFormPage.setProperty( propertyId );
			} );
			const referenceSnakValue = Util.getTestString( 'referenceSnak' );
			addReferenceFormPage.setSnakValue( referenceSnakValue );
			addReferenceFormPage.addButton().click();

			editStatementFormPage.references().first().then( ( element ) => {
				const valueForm = new ValueForm( element );
				valueForm.textInput().should( 'have.value', referenceSnakValue );
			} );

			editStatementFormPage.publishButton().click();
			editStatementFormPage.form().should( 'not.exist' );

			itemViewPage.referencesSections().first().then( ( element ) => {
				itemViewPage.referencesAccordion( element ).click();
				itemViewPage.references( element ).should( 'contain.text', referenceSnakValue );
			} );
		} );
	} );
} );
