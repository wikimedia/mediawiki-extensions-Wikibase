import Chainable = Cypress.Chainable;

export class ItemViewPage {

	public static STATEMENTS = '#wikibase-wbui2025-statementgrouplistview';

	public static VUE_CLIENTSIDE_RENDERED = '[data-v-app]';

	public static EDIT_LINKS = '.wikibase-wbui2025-edit-link';

	public static MAIN_SNAK_VALUES = '.wikibase-wbui2025-main-snak .wikibase-wbui2025-snak-value';

	public static QUALIFIERS_SECTION = '.wikibase-wbui2025-qualifiers';

	public static QUALIFIERS = '.wikibase-wbui2025-qualifier';

	public static REFERENCES_SECTION = '.wikibase-wbui2025-references';

	public static REFERENCES = '.wikibase-wbui2025-reference';

	public static MAIN_SNAKS = '.wikibase-wbui2025-main-snak';

	public static RANK_ICON = '.wikibase-rankselector span.ui-icon';

	private itemId: string;

	public constructor( itemId: string ) {
		this.itemId = itemId;
	}

	public open( lang: string = 'en' ): this {
		// We force tests to be in English be default, to be able to make assertions
		// about texts (especially, for example, selecting items from a Codex MenuButton
		// menu) without needing to modify Codex components or introduce translation
		// support to Cypress.
		cy.visitTitleMobile( { title: 'Item:' + this.itemId, qs: { uselang: lang } } );
		return this;
	}

	public statementsSection(): this {
		cy.get( ItemViewPage.STATEMENTS );
		return this;
	}

	public editLinks(): Chainable {
		return cy.get( ItemViewPage.VUE_CLIENTSIDE_RENDERED + ' ' + ItemViewPage.EDIT_LINKS );
	}

	public mainSnakValues(): Chainable {
		return cy.get( ItemViewPage.MAIN_SNAK_VALUES );
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

	public mainSnaks(): Chainable {
		return cy.get( ItemViewPage.MAIN_SNAKS );
	}

	public rank( context: HTMLElement ): Chainable {
		return cy.get( ItemViewPage.RANK_ICON, { withinSubject: context } );
	}

	public getClassForRank( rank: string ): string {
		return 'wikibase-rankselector-' + rank;
	}
}
