export class SpecialVersionPage {
	public open(): this {
		cy.visitTitle( 'Special:Version' );
		return this;
	}

	public checkWikibaseRepositoryExtensionLoaded(): this {
		cy.get( '#mw-version-ext-wikibase-WikibaseRepository' );
		return this;
	}
}
