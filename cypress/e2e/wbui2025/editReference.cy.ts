import { Util } from 'cypress-wikibase-api';

import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { LoginPage } from '../../support/pageObjects/LoginPage';
import { ValueForm } from '../../support/pageObjects/ValueForm';

describe( 'wbui2025 edit references', () => {
	context( 'mobile view', () => {
		const snakToDelete = 'single snak to delete';

		beforeEach( () => {
			const loginPage = new LoginPage();
			cy.task(
				'MwApi:CreateUser',
				{ usernamePrefix: 'mextest' },
			).then( ( { username, password } ) => {
				loginPage.login( username, password );
			} );

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
							cy.wrap( itemId ).as( 'itemId' );
						} );
					cy.get( '@itemId' ).then( ( itemId ) => {
						cy.task( 'MwApi:GetEntityData', { entityId: itemId } ).then( ( data ) => {
							const snaks1 = {
								[ propertyId ]: [ {
									snaktype: 'value',
									property: propertyId,
									datavalue: {
										value: snakToDelete,
										type: 'string',
									},
									datatype: 'string',
								} ],
							};

							const snaks2 = {
								[ propertyId ]: [
									{
										snaktype: 'value',
										property: propertyId,
										datavalue: {
											value: 'first snak',
											type: 'string',
										},
										datatype: 'string',
									}, {
										snaktype: 'value',
										property: propertyId,
										datavalue: {
											value: 'second snak',
											type: 'string',
										},
										datatype: 'string',
									},
								],
							};

							cy.task( 'MwApi:BotRequest', {
								isEdit: true,
								isPost: true,
								parameters: {
									action: 'wbsetreference',
									statement: data.claims[ propertyId ][ 0 ].id,
									snaks: JSON.stringify( snaks1 ),
								},
							} );
							cy.task( 'MwApi:BotRequest', {
								isEdit: true,
								isPost: true,
								parameters: {
									action: 'wbsetreference',
									statement: data.claims[ propertyId ][ 0 ].id,
									snaks: JSON.stringify( snaks2 ),
								},
							} );
						} );
					} );
				} );
			cy.viewport( 375, 1280 );
		} );
		it( 'references are editable and deletable', () => {
			cy.get( '@itemId' ).then( ( itemId ) => {
				const itemViewPage = new ItemViewPage( itemId );
				itemViewPage.open().statementsSection();
				itemViewPage.editLinks().first().click();
				const editFormPage = new EditStatementFormPage();
				editFormPage.referencesAccordion().click();

				editFormPage.references().should( 'have.length', 2 );

				const editedSnakValue = Util.getTestString( 'edited-snak' );
				editFormPage.valueForms().first().within( () => {
					editFormPage.references().eq( 1 ).within( ( element ) => {
						const valueForm = new ValueForm( element );
						valueForm.textInput().first().type( '{selectAll}{backspace}' + editedSnakValue );
					} );

					editFormPage.references().first().within( ( element ) => {
						( new ValueForm( element ) ).removeSnakButton().first().click();
					} );
				} );

				editFormPage.publishButton().click();

				// Verify the edits
				editFormPage.valueForms().should( 'not.exist' );
				itemViewPage.referencesSections().first().then( ( element ) => {
					itemViewPage.referencesAccordion( element ).click();
					itemViewPage.references( element ).should( 'have.length', 1 );
					itemViewPage.references( element ).should( 'not.contain.text', snakToDelete );
					itemViewPage.references( element ).should( 'contain.text', editedSnakValue );
				} );

				cy.task( 'MwApi:GetEntityData', { entityId: itemId } ).then( ( data ) => {
					cy.get( '@propertyId' ).then( ( propertyId ) => {
						cy.wrap( data.claims[ propertyId ][ 0 ].references )
							.should( 'have.length', 1 );
						cy.wrap( data.claims[ propertyId ][ 0 ].references[ 0 ].snaks[ propertyId ] )
							.should( 'have.length', 2 );
					} );
				} );

				// Edit again, delete the reference with 2 snaks
				itemViewPage.editLinks().first().click();
				editFormPage.referencesAccordion().click();
				editFormPage.references().should( 'have.length', 1 );
				editFormPage.valueForms().first().then( ( element: HTMLElement ) => {
					const valueForm = new ValueForm( element );
					editFormPage.references().first().within( () => {
						valueForm.removeReferenceButton().click();
					} );
				} );

				editFormPage.publishButton().click();

				// Verify the edits
				editFormPage.valueForms().should( 'not.exist' );
				itemViewPage.statementsSection().should( 'contain.text', '0 references' );

				cy.task( 'MwApi:GetEntityData', { entityId: itemId } ).then( ( data ) => {
					cy.get( '@propertyId' ).then( ( propertyId ) => {
						cy.wrap( data.claims[ propertyId ][ 0 ].references ).should( 'be.undefined' );
					} );
				} );

			} );
		} );
	} );
} );
