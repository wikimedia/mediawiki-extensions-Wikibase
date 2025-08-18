import Chainable = Cypress.Chainable;

export class ItemViewPage {

	public static STATEMENTS = '#wikibase-wbui2025-statementgrouplistview';

	public static EDIT_LINKS = '.wikibase-wbui2025-edit-link';

	private itemId: string;

	public constructor( itemId: string ) {
		this.itemId = itemId;
	}

	public open(): this {
		cy.visitTitleMobile( 'Item:' + this.itemId );
		return this;
	}

	public statementsSection(): this {
		cy.get( ItemViewPage.STATEMENTS );
		return this;
	}

	public editLinks(): Chainable {
		return cy.get( ItemViewPage.EDIT_LINKS );
	}
}
