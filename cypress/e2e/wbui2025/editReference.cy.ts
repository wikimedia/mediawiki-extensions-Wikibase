import { Util } from 'cypress-wikibase-api';

import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';
import { LoginPage } from '../../support/pageObjects/LoginPage';
import { ValueForm } from '../../support/pageObjects/ValueForm';

describe( 'wbui2025 edit references', () => {
	context( 'mobile view', () => {
		const snakToDelete = 'single snak to delete';
		let propertyName1: string;
		let propertyName2: string;

		beforeEach( () => {
			const loginPage = new LoginPage();
			propertyName1 = Util.getTestString( 'string-property' );
			propertyName2 = Util.getTestString( 'string-property' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName1,
				data: { datatype: 'string' },
			} ).as( 'propertyId1' );
			cy.task( 'MwApi:CreateProperty', {
				label: propertyName2,
				data: { datatype: 'string' },
			} ).as( 'propertyId2' );

			cy.task(
				'MwApi:CreateUser',
				{ usernamePrefix: 'mextest' },
			).then( ( { username, password } ) => {
				loginPage.login( username, password );
			} );

			cy.get( '@propertyId1' ).then( ( propertyId1 ) => {
				const statementData = {
					claims: [ {
						mainsnak: {
							snaktype: 'value',
							property: propertyId1,
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
							[ propertyId1 ]: [ {
								snaktype: 'value',
								property: propertyId1,
								datavalue: {
									value: snakToDelete,
									type: 'string',
								},
								datatype: 'string',
							} ],
						};

						const snaks2 = {
							[ propertyId1 ]: [
								{
									snaktype: 'value',
									property: propertyId1,
									datavalue: {
										value: 'first snak',
										type: 'string',
									},
									datatype: 'string',
								}, {
									snaktype: 'value',
									property: propertyId1,
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
								statement: data.claims[ propertyId1 ][ 0 ].id,
								snaks: JSON.stringify( snaks1 ),
							},
						} );
						cy.task( 'MwApi:BotRequest', {
							isEdit: true,
							isPost: true,
							parameters: {
								action: 'wbsetreference',
								statement: data.claims[ propertyId1 ][ 0 ].id,
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

				// First reference: delete the only snak
				// Second reference: edit the first snak, add a new snak
				const editedSnakValue = Util.getTestString( 'edited-snak' );
				const newSnakValue = Util.getTestString( 'new-snak' );
				editFormPage.valueForms().first().within( () => {
					editFormPage.references().eq( 1 ).within( ( element ) => {
						const valueForm = new ValueForm( element );
						valueForm.textInput().first().type( '{selectAll}{backspace}' + editedSnakValue );

						valueForm.addSnakButton().first().click();
						valueForm.textInput().eq( 3 ).should( 'be.disabled' );
						valueForm.selectPropertyFromLookup( propertyName2 );
						valueForm.textInput().eq( 3 ).type( newSnakValue );

					} );

					editFormPage.references().first().within( ( element ) => {
						( new ValueForm( element ) ).removeSnakButton().first().click();
					} );
				} );

				editFormPage.publishButton().click();

				// Verify the edits
				editFormPage.formRoot().should( 'not.exist' );
				itemViewPage.referencesSections().first().then( ( element ) => {
					itemViewPage.referencesAccordion( element ).click();
					itemViewPage.references( element )
						.should( 'have.length', 1 )
						.should( 'not.contain.text', snakToDelete )
						.should( 'contain.text', editedSnakValue )
						.should( 'contain.text', newSnakValue )
						.should( 'contain.text', propertyName2 );
				} );

				cy.task( 'MwApi:GetEntityData', { entityId: itemId } ).then( ( data ) => {
					cy.get( '@propertyId1' ).then( ( propertyId1 ) => {
						cy.wrap( data.claims[ propertyId1 ][ 0 ].references )
							.should( 'have.length', 1 );
						cy.wrap( data.claims[ propertyId1 ][ 0 ].references[ 0 ].snaks[ propertyId1 ] )
							.should( 'have.length', 2 );
						cy.get( '@propertyId2' ).then( ( propertyId2 ) => {
							cy.wrap( data.claims[ propertyId1 ][ 0 ].references[ 0 ].snaks[ propertyId2 ] )
								.should( 'have.length', 1 );
						} );
					} );
				} );

				// Edit again, delete the reference with 3 snaks
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
				editFormPage.formRoot().should( 'not.exist' );
				itemViewPage.statementsSection().should( 'contain.text', '0 references' );

				cy.task( 'MwApi:GetEntityData', { entityId: itemId } ).then( ( data ) => {
					cy.get( '@propertyId1' ).then( ( propertyId ) => {
						cy.wrap( data.claims[ propertyId ][ 0 ].references ).should( 'be.undefined' );
					} );
				} );

			} );
		} );
	} );
} );
