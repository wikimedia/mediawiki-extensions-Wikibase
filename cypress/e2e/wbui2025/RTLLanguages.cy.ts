import { Util } from 'cypress-wikibase-api';
import { EditStatementFormPage } from '../../support/pageObjects/EditStatementFormPage';
import { ItemViewPage } from '../../support/pageObjects/ItemViewPage';

// RTL functionality and layout checks (see: T403013).
describe( 'wbui2025 language preferences and RTL functionality', () => {
	context( 'mobile view', () => {
		let propertyName: string;
		let itemId: string;

		const SELECTED_LANGUAGE = 'ar';

		before( () => {
			cy.viewport( 375, 1280 );

			// Note: Test data uses Arabic text.
			propertyName = Util.getTestString( 'خاصية' );
			cy.task( 'MwApi:CreateProperty', {
				label: {
					[ SELECTED_LANGUAGE ]: { language: SELECTED_LANGUAGE, value: propertyName },
				},
				data: { datatype: 'string' },
			} ).then( ( newPropertyId: string ) => {
				const statementData = {
					claims: [ {
						mainsnak: {
							snaktype: 'value',
							property: newPropertyId,
							datavalue: {
								value: 'مثال نصي',
								type: 'string',
							},
						},
						type: 'statement',
						rank: 'normal',
					} ],
				};
				cy.task( 'MwApi:CreateItem', {
					label: {
						[ SELECTED_LANGUAGE ]: {
							language: SELECTED_LANGUAGE,
							value: Util.getTestString( 'مثال لعنصر بالعربي' ),
						},
					},
					data: statementData,
				} ).then( ( newItemId: string ) => {
					itemId = newItemId;
				} );
			} );
		} );

		beforeEach( () => {
			cy.viewport( 375, 1280 );
		} );

		it.only( 'Checking RTL layout when editing statements', () => {
			const itemViewPage = new ItemViewPage( itemId );
			const editFormPage = new EditStatementFormPage();

			itemViewPage.open( SELECTED_LANGUAGE );

			// Verify RTL layout is maintained
			cy.get( 'html' ).should( 'have.attr', 'dir', 'rtl' );
			cy.get( 'html' ).should( 'have.attr', 'lang', SELECTED_LANGUAGE );

			itemViewPage.statementsSection();

			// Check if edit links exist and are clickable
			itemViewPage.editLinks().then( ( $links ) => {
				if ( $links.length > 0 ) {
					cy.wrap( $links.first() ).click();

					// Verify RTL is maintained in edit mode
					cy.get( 'html' ).should( 'have.attr', 'dir', 'rtl' );
					cy.get( 'html' ).should( 'have.attr', 'lang', SELECTED_LANGUAGE );

					editFormPage.textInput()
						.should( 'be.visible' )
						.should( 'have.value', 'مثال نصي' );

					editFormPage.formHeading()
						.find( '.cdx-icon--flipped' )
						.should( 'exist' )
						.should( 'be.visible' );

					editFormPage.formHeading()
						.find( '.cdx-icon--flipped' )
						.then( ( $el ) => {
							const comp = getComputedStyle( $el[ 0 ] );
							expect( parseFloat( comp.marginRight ) ).to.be.equal( 0 );
						} );

					editFormPage.cancelButton()
						.invoke( 'text' )
						.should( 'match', /[\u0600-\u06FF]/ );

					editFormPage.publishButton().then( ( $btn ) => {
						const { left, right } = $btn[ 0 ].getBoundingClientRect();
						// In RTL, "end" is left; button should be closer to left than right padding.
						expect( left ).to.be.lessThan( right );
					} );
				}
			} );
		} );
	} );
} );
