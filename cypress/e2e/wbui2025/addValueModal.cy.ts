import { Util } from 'cypress-wikibase-api';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { AddValueModal } from '../../support/pageObjects/AddValueModal';

describe( 'wbui2025 item view add additional value to existing statement', () => {
	let propertyName1: string;
	let itemId1: string;
	let propertyName2: string;
	let itemId2: string;

	before( () => {
		propertyName1 = Util.getTestString( 'modal-property' );
		cy.task( 'MwApi:CreateProperty', {
			label: propertyName1,
			data: { datatype: 'string' },
		} ).then( ( newPropertyId: string ) => {
			const statementData = {
				claims: [
					{
						mainsnak: {
							snaktype: 'value',
							property: newPropertyId,
							datavalue: { value: 'ExampleString', type: 'string' },
						},
						type: 'statement',
						rank: 'normal',
					},
				],
			};
			cy.task( 'MwApi:CreateItem', {
				label: Util.getTestString( 'modal-item' ),
				data: statementData,
			} ).then( ( newItemId: string ) => {
				itemId1 = newItemId;
			} );
		} );

		propertyName2 = Util.getTestString( 'modal-property-cancel' );
		cy.task( 'MwApi:CreateProperty', {
			label: propertyName2,
			data: { datatype: 'string' },
		} ).then( ( newPropertyId: string ) => {
			const statementData = {
				claims: [
					{
						mainsnak: {
							snaktype: 'value',
							property: newPropertyId,
							datavalue: { value: 'CancelString', type: 'string' },
						},
						type: 'statement',
						rank: 'normal',
					},
				],
			};
			cy.task( 'MwApi:CreateItem', {
				label: Util.getTestString( 'modal-item-cancel' ),
				data: statementData,
			} ).then( ( id: string ) => {
				itemId2 = id;
			} );
		} );
	} );

	context( 'full add-value workflow', () => {
		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

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
		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

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
