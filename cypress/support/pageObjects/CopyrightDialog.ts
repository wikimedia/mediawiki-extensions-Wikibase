import Chainable = Cypress.Chainable;

export class CopyrightDialog {
	public static DIALOG = '.wikibase-wbui2025-copyright-dialog';

	public static CHECKBOX = '.cdx-checkbox input';

	public static CONFIRM_BUTTON = '.cdx-dialog__footer__primary-action';

	public root(): Chainable {
		return cy.get( CopyrightDialog.DIALOG );
	}

	public clickCheckbox(): void {
		this.root().find( CopyrightDialog.CHECKBOX ).click();
	}

	public confirmButton(): Chainable {
		return this.root().find( CopyrightDialog.CONFIRM_BUTTON );
	}
}
