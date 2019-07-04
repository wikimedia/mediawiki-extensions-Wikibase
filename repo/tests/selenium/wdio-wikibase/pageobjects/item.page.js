'use strict';

const Page = require( 'wdio-mediawiki/Page' ),
	MixinBuilder = require( '../pagesections/mixinbuilder' ),
	MainStatementSection = require( '../pagesections/main.statement.section' ),
	ComponentInteraction = require( '../pagesections/ComponentInteraction' ),
	PageMixture = MixinBuilder.mix( Page ).with( MainStatementSection, ComponentInteraction );

class ItemPage extends PageMixture {
	static get ITEM_WIDGET_SELECTORES() {
		return {
			MAIN_STATEMENTS: 'div.wikibase-entityview-main > .wikibase-statementgrouplistview',
			ADD_STATEMENT: 'div.wikibase-addtoolbar > .wikibase-toolbar-button-add > a',
			SAVE_BUTTON: '.wikibase-toolbar-button-save',
			EDIT: '.wikibase-toolbar-container .wikibase-toolbar-button-edit',
			PROPERTY_INPUT: '.ui-entityselector-input',
			VALUE_INPUT: '.valueview-input',
			ITEM_DESCRIPTION_INPUT: '.wikibase-descriptionview-text .wikibase-descriptionview-input',
			QUALIFIERS: '.wikibase-statementview-qualifiers .listview-item',
			REFERENCES: '.wikibase-statementview-references',
			NTH_ELEMENT: '.wikibase-listview > .listview-item',
			RECENT_CHANGES: '#n-recentchanges',
			LASTCHANGE_HISTORY: '.mw-changeslist-last .mw-changeslist-history',
			REVISION: '#pagehistory',
			REVISION_DATE_LINK: '.after .mw-changeslist-date'
		};
	}

	open( entityId ) {
		super.openTitle( 'Special:EntityPage/' + entityId );
	}

	get statements() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.MAIN_STATEMENTS ).$$( '.wikibase-statementgroupview' );
	}

	get addStatementLink() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.ADD_STATEMENT );
	}

	get propertyInputField() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.PROPERTY_INPUT );
	}

	get valueInputField() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.VALUE_INPUT );
	}

	get editButton() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.EDIT );
	}

	get saveButton() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.SAVE_BUTTON );
	}

	get recentChanges() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.RECENT_CHANGES );
	}

	get lastChangeHistory() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.LASTCHANGE_HISTORY );
	}

	get descriptionInputField() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.ITEM_DESCRIPTION_INPUT );
	}

	getNthQualifierPropertyInput( statement, qualifierIndex ) {
		let qualifier = statement.$$( this.constructor.ITEM_WIDGET_SELECTORES.QUALIFIERS )[ qualifierIndex ];
		return qualifier.$( this.constructor.ITEM_WIDGET_SELECTORES.PROPERTY_INPUT );
	}

	getNthQualifierValueInput( statement, qualifierIndex ) {
		let qualifier = statement.$$( this.constructor.ITEM_WIDGET_SELECTORES.QUALIFIERS )[ qualifierIndex ];
		return qualifier.$( this.constructor.ITEM_WIDGET_SELECTORES.VALUE_INPUT );
	}

	getNthReferencePropertyInput( statement, referenceIndex ) {
		let reference = statement.$$( this.constructor.ITEM_WIDGET_SELECTORES.REFERENCES )[ referenceIndex ];
		return reference.$( this.constructor.ITEM_WIDGET_SELECTORES.PROPERTY_INPUT );
	}

	getNthReferenceValueInput( statement, referenceIndex ) {
		let reference = statement.$$( this.constructor.ITEM_WIDGET_SELECTORES.REFERENCES )[ referenceIndex ];
		return reference.$( this.constructor.ITEM_WIDGET_SELECTORES.VALUE_INPUT );
	}

	editItemDescription( description ) {

		this.editButton.waitForExist( 3000 );
		browser.pause( 100 );
		this.editButton.click();
		this.descriptionInputField.waitForExist();
		this.descriptionInputField.setValue( description );
		this.saveButton.waitForExist( 1000 );
		this.saveButton.click();
	}

	goToPreviousRevision() {
		this.recentChanges.click();
		this.lastChangeHistory.waitForExist( 1000 );
		this.lastChangeHistory.click();
		let revisionList = $( this.constructor.ITEM_WIDGET_SELECTORES.REVISION );
		revisionList.$( this.constructor.ITEM_WIDGET_SELECTORES.REVISION_DATE_LINK ).waitForExist();
		revisionList.$( this.constructor.ITEM_WIDGET_SELECTORES.REVISION_DATE_LINK ).click();
	}

	isSaveButtonEnabled() {
		return $( this.constructor.ITEM_WIDGET_SELECTORES.SAVE_BUTTON ).getAttribute( 'aria-disabled' ) === 'false';
	}
}

module.exports = new ItemPage();
