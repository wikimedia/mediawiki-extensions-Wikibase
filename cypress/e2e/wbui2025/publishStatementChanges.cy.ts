import { Util } from 'cypress-wikibase-api';

import { checkA11y } from '../../support/checkA11y';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { ValueForm } from '../../support/pageObjects/ValueForm';

describe( 'wbui2025 item view publish statement changes', () => {
	context( 'mobile view', () => {
		let propertyName: string;
		const initialPropertyValue: string = 'ExampleString';
		let itemId: string;

		before( () => {
			propertyName = Util.getTestString( 'property' );
			cy.task( 'MwApi:CreateProperty', { label: propertyName, data: { datatype: 'string' } } )
				.then( ( newPropertyId: string ) => {
					const exampleSnak = {
						snaktype: 'value',
						property: newPropertyId,
						datavalue: {
							value: initialPropertyValue,
							type: 'string',
						},
					};
					const qualifiers = {};
					qualifiers[ newPropertyId ] = [ Object.assign( {}, exampleSnak ) ];
					const snaks = {};
					snaks[ newPropertyId ] = [ Object.assign( {}, exampleSnak ) ];
					const references = [
						{
							snaks,
							'snaks-order': [ newPropertyId ],
						},
					];
					const statementData = {
						claims: [ {
							mainsnak: Object.assign( {}, exampleSnak ),
							qualifiers,
							'qualifiers-order': [ newPropertyId ],
							references,
							type: 'statement',
							rank: 'normal',
						} ],
					};
					cy.task( 'MwApi:CreateItem', { label: Util.getTestString( 'item' ), data: statementData } )
						.then( ( newItemId: string ) => {
							itemId = newItemId;
						} );
				} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it( 'loads the item view and shows a statement, which can be edited', () => {
			const itemViewPage = new ItemViewPage( itemId );
			itemViewPage.open().statementsSection();
			checkA11y( ItemViewPage.STATEMENTS );

			/* Open up the edit form */
			itemViewPage.editLinks().first().click();
			const editFormPage = new EditStatementFormPage();
			editFormPage.propertyName().should( 'have.text', propertyName );

			/* Check that references and qualifiers are present */
			itemViewPage.qualifiersSections().first().then( ( element ) => {
				itemViewPage.qualifiers( element ).should( 'have.length', 1 );
			} );
			itemViewPage.referencesSections().first().then( ( element ) => {
				itemViewPage.references( element ).should( 'have.length', 1 );
			} );

			const newPropertyValue = 'newPropertyValue';
			const secondStatementValue = 'newStatementValue';
			editFormPage.valueForms();
			editFormPage.valueForms().first().then( ( element: HTMLElement ) => {
				/* Change the value of the first statement */
				const valueForm = new ValueForm( element );
				valueForm.valueInput().invoke( 'val' ).should( 'equal', initialPropertyValue );
				editFormPage.publishButton().should( 'not.be.disabled' );
				valueForm.setValueInput( newPropertyValue );
			} );
			/* Add a second statement value */
			editFormPage.addValueButtons().last().click();
			editFormPage.valueForms().last().then( ( element: HTMLElement ) => {
				/* Enter a new value for the second statement */
				const valueForm = new ValueForm( element );
				valueForm.valueInput().invoke( 'val' ).should( 'equal', '' );
				valueForm.setValueInput( secondStatementValue );
			} );

			/* Save changes by clicking 'publish' */
			editFormPage.publishButton().click();
			editFormPage.publishButton().should( 'be.disabled' );

			/* Wait for the form to close, and check the value is changed */
			editFormPage.valueForms().should( 'not.exist' );
			itemViewPage.snakValues().first().should( 'have.text', newPropertyValue );

			/* Check that references and qualifiers are still present */
			itemViewPage.qualifiersSections().first().then( ( element ) => {
				itemViewPage.qualifiers( element ).should( 'have.length', 1 );
			} );
			itemViewPage.referencesSections().first().then( ( element ) => {
				itemViewPage.references( element ).should( 'have.length', 1 );
			} );

			/* Reopen the form to check that the values in the form is are also updated */
			itemViewPage.editLinks().first().click();
			editFormPage.valueForms().first().then( ( element: HTMLElement ) => {
				const valueForm = new ValueForm( element );
				valueForm.valueInput().invoke( 'val' ).should( 'equal', newPropertyValue );
			} );
			editFormPage.valueForms().last().then( ( element: HTMLElement ) => {
				const valueForm = new ValueForm( element );
				valueForm.valueInput().invoke( 'val' ).should( 'equal', secondStatementValue );
			} );

			/* Reload page and check that data was saved */
			itemViewPage.open().statementsSection();
			checkA11y( ItemViewPage.STATEMENTS );
		} );
	} );
} );
