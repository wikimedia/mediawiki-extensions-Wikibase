import Chainable = Cypress.Chainable;

export class AddStatementFormPage {
	public static SELECTORS = {
		PROPERTY_LOOKUP: '.wikibase-wbui2025-add-statement-form_property-selector',
		PROPERTY_INPUT: '.wikibase-wbui2025-property-lookup input',
		SUBMIT_BUTTONS: '.wikibase-wbui2025-modal-overlay__footer__actions > .cdx-button',
		SNAK_VALUE_INPUT: '.wikibase-wbui2025-edit-statement-snak-value input',
		FORM: '.wikibase-wbui2025-add-statement-form',
	};

	public propertyLookup(): Chainable {
		return cy.get( AddStatementFormPage.SELECTORS.PROPERTY_LOOKUP );
	}

	public propertyInput(): Chainable {
		return cy.get( AddStatementFormPage.SELECTORS.PROPERTY_INPUT );
	}

	public publishButton(): Chainable {
		return cy.get( AddStatementFormPage.SELECTORS.SUBMIT_BUTTONS ).last();
	}

	public snakValueInput(): Chainable {
		return cy.get( AddStatementFormPage.SELECTORS.SNAK_VALUE_INPUT );
	}

	public cancelButton(): Chainable {
		return cy.get( AddStatementFormPage.SELECTORS.SUBMIT_BUTTONS ).first();
	}

	public form(): Chainable {
		return cy.get( AddStatementFormPage.SELECTORS.FORM );
	}

	public setProperty( searchTerm: string ): this {
		this.propertyInput().clear();
		this.propertyInput().type( searchTerm, { parseSpecialCharSequences: false } );
		this.propertyInput().should( 'have.value', searchTerm );
		this.propertyInput().focus();

		cy.get( '.wikibase-wbui2025-property-lookup .cdx-menu' ).should( 'be.visible' );
		this.getFirstPropertyLookupItem().click();

		return this;
	}

	public getFirstPropertyLookupItem(): Chainable {
		return cy.get( '.wikibase-wbui2025-property-lookup .cdx-menu-item:first:not(.cdx-menu__no-results)' );
	}

	public setSnakValue( inputText: string ): this {
		this.snakValueInput().type( inputText );
		return this;
	}

	public selectFirstSnakValueLookupItem(): this {
		cy.get( '.wikibase-wbui2025-snak-value .cdx-menu-item:first' ).click();
		return this;
	}

}
