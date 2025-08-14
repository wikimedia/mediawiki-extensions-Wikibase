export class ItemViewPage {

	// eslint-disable-next-line es-x/no-class-fields
	private itemId: string;

	public constructor( itemId: string ) {
		this.itemId = itemId;
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
