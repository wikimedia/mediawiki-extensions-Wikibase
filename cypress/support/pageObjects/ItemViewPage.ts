import Chainable = Cypress.Chainable;

export class ItemViewPage {

	public static STATEMENTS = '#wikibase-wbui2025-statementgrouplistview';

	public static VUE_CLIENTSIDE_RENDERED = '[data-v-app]';

	public static EDIT_LINKS = '.wikibase-wbui2025-edit-link';

	public static SNAK_VALUES = '.wikibase-wbui2025-snak-value > p';

	public static QUALIFIERS_SECTION = '.wikibase-wbui2025-qualifiers';

	public static QUALIFIERS = '.wikibase-wbui2025-qualifier';

	public static REFERENCES_SECTION = '.wikibase-wbui2025-references';

	public static REFERENCES = '.wikibase-wbui2025-reference';

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

	public qualifiersSections(): Chainable {
		return cy.get( ItemViewPage.QUALIFIERS_SECTION );
	}

	public qualifiers( context: HTMLElement ): Chainable {
		return cy.get( ItemViewPage.QUALIFIERS, { withinSubject: context } );
	}

	public referencesSections(): Chainable {
		return cy.get( ItemViewPage.REFERENCES_SECTION );
	}

	public references( context: HTMLElement ): Chainable {
		return cy.get( ItemViewPage.REFERENCES, { withinSubject: context } );
	}
}
