export class ItemViewPage {

	public static STATEMENTS = '#wikibase-wbui2025-statementgrouplistview';

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
}
