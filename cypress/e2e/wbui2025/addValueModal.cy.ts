import { Util } from 'cypress-wikibase-api';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddValueModal } from '../../support/pageObjects/AddValueModal';

describe( 'wbui2025 item view add additional value to existing statement', () => {
	let itemId1: string;
	let itemId2: string;

	before( () => {
		cy.task( 'MwApi:GetOrCreatePropertyIdByDataType', { datatype: 'string' } )
			.then( ( propertyId: string ) => {
				const statementData1 = {
					claims: [
						{
							mainsnak: {
								snaktype: 'value',
								property: propertyId,
								datavalue: { value: 'ExampleString', type: 'string' },
							},
							type: 'statement',
							rank: 'normal',
						},
					],
				};
				const statementData2 = {
					claims: [
						{
							mainsnak: {
								snaktype: 'value',
								property: propertyId,
								datavalue: { value: 'CancelString', type: 'string' },
							},
							type: 'statement',
							rank: 'normal',
						},
					],
				};
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'modal-item' ),
					data: statementData1,
				} ).then( ( newItemId: string ) => {
					itemId1 = newItemId;
				} );
				cy.task( 'MwApi:CreateItem', {
					label: Util.getTestString( 'modal-item-cancel' ),
					data: statementData2,
				} ).then( ( id: string ) => {
					itemId2 = id;
				} );
			} );
	} );

	beforeEach( () => {
		cy.viewport( 375, 1280 );
	} );

	context( 'full add-value workflow', () => {
		it( 'opens the add-value modal, searches, selects, and confirms value', () => {
			const itemPage = new ItemViewPage( itemId1 );
			itemPage.open().statementsSection();
			itemPage.editLinks().first().click();
			const edit = new EditStatementFormPage();
			edit.addValueButtons().first().click();
			const modal = new AddValueModal();
			modal.modal().should( 'be.visible' );
			modal.lookupInput().clear().type( 'Weather' );
			modal.confirmButton().click();
			modal.modal().should( 'not.exist' );
			edit.formRoot().should( 'exist' );
			edit.valueForms().should( 'have.length.at.least', 2 );
		} );
	} );

	context( 'cancel behavior', () => {
		it( 'opens the add-value modal and cancels cleanly', () => {
			const itemPage = new ItemViewPage( itemId2 );
			itemPage.open().statementsSection();
			itemPage.editLinks().first().click();
			const edit = new EditStatementFormPage();
			edit.addValueButtons().first().click();
			const modal = new AddValueModal();
			modal.modal().should( 'be.visible' );
			modal.cancelButton().click();
			cy.get( AddValueModal.SELECTORS.ROOT ).should( 'not.exist' );
			edit.formRoot().should( 'be.visible' );
		} );
	} );
} );
