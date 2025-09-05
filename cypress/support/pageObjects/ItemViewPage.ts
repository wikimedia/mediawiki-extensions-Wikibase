import Chainable = Cypress.Chainable;

export class ItemViewPage {

	public static STATEMENTS = '#wikibase-wbui2025-statementgrouplistview';

	public static VUE_CLIENTSIDE_RENDERED = '[data-v-app]';

	public static EDIT_LINKS = '.wikibase-wbui2025-edit-link';

	public static SNAK_VALUES = '.wikibase-wbui2025-snak-value > p';

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
		return cy.get( ItemViewPage.VUE_CLIENTSIDE_RENDERED + ' ' + ItemViewPage.EDIT_LINKS );
	}

	public snakValues(): Chainable {
		return cy.get( ItemViewPage.SNAK_VALUES );
	}
}
