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
					const statementData = {
						claims: [ {
							mainsnak: {
								snaktype: 'value',
								property: newPropertyId,
								datavalue: {
									value: initialPropertyValue,
									type: 'string',
								},
							},
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
			const newPropertyValue = 'newPropertyValue';
			editFormPage.valueForms();
			editFormPage.valueForms().first().then( ( element: HTMLElement ) => {
				/* Change the value of the first property */
				const valueForm = new ValueForm( element );
				valueForm.valueInput().invoke( 'val' ).should( 'equal', initialPropertyValue );
				valueForm.publishButton().should( 'not.have.class', 'inactive' );
				valueForm.setValueInput( newPropertyValue );
				/* Save changes by clicking 'publish' */
				valueForm.publishButton().click();
				valueForm.publishButton().should( 'have.class', 'inactive' );
			} );

			/* Wait for the form to close, and check the value is changed */
			editFormPage.valueForms().should( 'not.exist' );
			itemViewPage.snakValues().first().should( 'have.text', newPropertyValue );

			/* Reopen the form to check that the value in the form is also updated */
			itemViewPage.editLinks().first().click();
			editFormPage.valueForms().first().then( ( element: HTMLElement ) => {
				const valueForm = new ValueForm( element );
				valueForm.valueInput().invoke( 'val' ).should( 'equal', newPropertyValue );
			} );
		} );
	} );
} );
