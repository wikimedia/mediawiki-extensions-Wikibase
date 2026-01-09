import Chainable = Cypress.Chainable;

export class AddValueModal {
	public static SELECTORS = {
		ROOT: '.wikibase-wbui2025-add-value-modal',
		HEADER: '.wikibase-wbui2025-add-statement-value-heading',
		MENU: '.cdx-menu',
		MENU_ITEM: '.cdx-menu-item',
		CONFIRM_BUTTON:
			'.wikibase-wbui2025-add-statement-value .wikibase-wbui2025-edit-form-actions .cdx-button--action-progressive',
		CANCEL_BUTTON:
			'.wikibase-wbui2025-add-statement-value .wikibase-wbui2025-edit-form-actions .cdx-button--weight-quiet',
	};

	/** The ONLY root of the AddValue modal */
	private root(): Chainable {
		return cy.get( AddValueModal.SELECTORS.ROOT );
	}

	public modal(): Chainable {
		return this.root();
	}

	public header(): Chainable {
		return this.root().find( AddValueModal.SELECTORS.HEADER );
	}

	public lookupInput(): Chainable {
		return cy
			.get( AddValueModal.SELECTORS.ROOT )
			.find( '.wikibase-wbui2025-edit-statement-snak-value input' )
			.first();
	}

	public menu(): Chainable {
		return this.root().find( AddValueModal.SELECTORS.MENU );
	}

	public menuItems(): Chainable {
		return this.root()
			.find( AddValueModal.SELECTORS.MENU_ITEM )
			.filter( ':visible' )
			.should( 'have.length.gt', 0 );
	}

	public confirmButton(): Chainable {
		return cy
			.get( AddValueModal.SELECTORS.ROOT )
			.find( '.wikibase-wbui2025-edit-form-actions .cdx-button--action-progressive' )
			.first();
	}

	public cancelButton(): Chainable {
		return cy
			.get( AddValueModal.SELECTORS.ROOT )
			.find( '.wikibase-wbui2025-edit-form-actions .cdx-button--weight-quiet' )
			.first();
	}
}
