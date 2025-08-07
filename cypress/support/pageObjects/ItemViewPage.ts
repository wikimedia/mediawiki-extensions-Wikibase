export class ItemViewPage {

	itemId: string

	public constructor( itemId: string ) {
		this.itemId = itemId
	}

	public open(): this {
		cy.visitTitleMobile( 'Item:' + this.itemId );
		return this;
	}

	public statementsSection(): this {
		cy.get( '#wikibase-wbui2025-statementgrouplistview' );
		return this;
	}
}
