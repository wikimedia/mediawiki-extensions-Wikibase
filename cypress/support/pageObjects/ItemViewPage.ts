import Chainable = Cypress.Chainable;

export class ItemViewPage {

	public static STATEMENTS = '#wikibase-wbui2025-statementgrouplistview';

	public static STATEMENTS_HEADING = '#claims';

	public static VUE_CLIENTSIDE_RENDERED = '[data-v-app]';

	public static EDIT_LINKS = '.wikibase-wbui2025-edit-link';

	public static EDIT_LINKS_DELETED_PROPERTY = ' .wikibase-wbui2025-edit-link--deleted-property';

	public static PROPERTY_NAME_DELETED = ' .wikibase-wbui2025-property-name--deleted';

	public static MAIN_SNAK_VALUES = '.wikibase-wbui2025-main-snak .wikibase-wbui2025-snak-value span.snakValue';

	public static INVALID_SNAK_VALUE_ERROR = ' .wikibase-wbui2025-snak-value .wb-format-error';

	public static QUALIFIERS_SECTION = '.wikibase-wbui2025-qualifiers';

	public static QUALIFIERS = '.wikibase-wbui2025-qualifier';

	public static REFERENCES_SECTION = '.wikibase-wbui2025-references';

	public static REFERENCES_ACCORDION = '.wikibase-wbui2025-clickable';

	public static REFERENCES = '.wikibase-wbui2025-reference';

	public static MAIN_SNAKS = '.wikibase-wbui2025-main-snak';

	public static RANK_ICON = '.wikibase-wbui2025-rankselector span';

	public static ADD_STATEMENT_BUTTON = '.wikibase-wbui2025-add-statement-button>.cdx-button';

	public static ADD_STATEMENT_FLOATING_DISC = '.wikibase-wbui2025-add-statement-float-disc';

	public static ADD_STATEMENT_FLOATING_BUTTON = '.wikibase-wbui2025-add-statement-float-button';

	public static ADD_STATEMENT_FLOATING_BUTTON_CLOSE = '.wikibase-wbui2025-add-statement-float-button-close-icon';

	public static COMMONS_MEDIA_THUMBNAIL_LINK = 'span.snakValue div.thumb a.image';

	public static COMMONS_MEDIA_THUMBNAIL_IMAGE = 'span.snakValue div.thumb a.image span img';

	public static NOTIFICATION_DISMISS_BUTTON = '.cdx-message button.cdx-button';

	private itemId: string;

	public constructor( itemId: string ) {
		this.itemId = itemId;
	}

	public getItemId(): string {
		return this.itemId;
	}

	public open( lang: string = 'en' ): this {
		// We force tests to be in English be default, to be able to make assertions
		// about texts (especially, for example, selecting items from a Codex MenuButton
		// menu) without needing to modify Codex components or introduce translation
		// support to Cypress.
		cy.visitTitleMobile( { title: 'Item:' + this.itemId, qs: { uselang: lang } } )
			// turn ResourceLoader exceptions into unhandled Promise rejections so Cypress will make them visible
			.invoke( 'mw.trackSubscribe',
				'resourceloader.exception',
				( _topic, data ) => Promise.reject( data.exception || data ) );
		return this;
	}

	public statementsSection(): Chainable {
		return cy.get( ItemViewPage.STATEMENTS );
	}

	public statementsHeading(): Chainable {
		return cy.get( ItemViewPage.STATEMENTS_HEADING );
	}

	public editLinks(): Chainable {
		return cy.get( ItemViewPage.VUE_CLIENTSIDE_RENDERED + ' ' + ItemViewPage.EDIT_LINKS );
	}

	public deletedPropertyName(): Chainable {
		return cy.get( ItemViewPage.VUE_CLIENTSIDE_RENDERED + ItemViewPage.PROPERTY_NAME_DELETED );
	}

	public invalidSnakValueError(): Chainable {
		return cy.get( ItemViewPage.VUE_CLIENTSIDE_RENDERED + ItemViewPage.INVALID_SNAK_VALUE_ERROR );
	}

	public editLinksDeletedProperty(): Chainable {
		return cy.get( ItemViewPage.VUE_CLIENTSIDE_RENDERED + ItemViewPage.EDIT_LINKS_DELETED_PROPERTY );
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

	public referencesAccordion( context: HTMLElement ): Chainable {
		return cy.get( ItemViewPage.REFERENCES_ACCORDION, { withinSubject: context } );
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

	public addStatementButton(): Chainable {
		return cy.get( ItemViewPage.VUE_CLIENTSIDE_RENDERED + ' ' + ItemViewPage.ADD_STATEMENT_BUTTON );
	}

	public addStatementFloatingDisc(): Chainable {
		return cy.get( ItemViewPage.ADD_STATEMENT_FLOATING_DISC );
	}

	public addStatementFloatingButton(): Chainable {
		return cy.get( ItemViewPage.ADD_STATEMENT_FLOATING_BUTTON );
	}

	public addStatementFloatingButtonCloseIcon(): Chainable {
		return cy.get( ItemViewPage.ADD_STATEMENT_FLOATING_BUTTON_CLOSE );
	}

	public notificationDismissButton(): Chainable {
		return cy.get( ItemViewPage.NOTIFICATION_DISMISS_BUTTON );
	}

	public statementWrapper( propertyId: string ): Chainable {
		return cy.get( `#${ propertyId }` );
	}

	public scrollToTopOfStatementWrapper( propertyId: string ): Chainable {
		return this.statementWrapper( propertyId ).then( ( $el ) => {
			const rect = $el[ 0 ].getBoundingClientRect();
			const vpHeight = $el[ 0 ].ownerDocument.defaultView!.innerHeight;
			return cy.scrollTo( 0, rect.top - vpHeight );
		} );
	}

	public assertStatementIsInViewport( propertyId: string ): void {
		this.statementWrapper( propertyId ).should( ( $el ) => {
			const rect = $el[ 0 ].getBoundingClientRect();
			const vpHeight = $el[ 0 ].ownerDocument.defaultView!.innerHeight;
			expect( rect.top, 'statement top is within viewport' ).to.be.within( 0, vpHeight - 1 );
		} );
	}

	public getClassForRank( rank: string ): string {
		return 'wikibase-rankselector-' + rank;
	}
}
