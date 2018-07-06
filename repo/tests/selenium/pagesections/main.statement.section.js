'use strict';

let MainStatementSection = Base => class extends Base {

	static get STATEMENT_WIDGET_SELECTORS() {
		return {
			EDIT_INPUT_PROPERTY: '.ui-entityselector-input',
			EDIT_INPUT_VALUE: '.valueview-input',
			STATEMENT_VALUE: '.wikibase-snakview-value'
		};
	}

	static get TOOLBAR_WIDGET_SELECTORS() {
		return {
			EDIT_BUTTON: '.wikibase-toolbar-button-edit',
			REMOVE_BUTTON: '.wikibase-toolbar-button-remove',
			SAVE_BUTTON: '.wikibase-toolbar-button-save'
		};
	}

	get mainStatementsContainer() {
		return $( 'div.wikibase-entityview-main > .wikibase-statementgrouplistview' );
	}

	get addMainStatementLink() {
		return this.mainStatementsContainer.$( 'div.wikibase-addtoolbar > span > a' );
	}

	/**
	 * Add a statement
	 *
	 * N.B. A main statement is statement attached to an entity e.g. statements on Items and Properties
	 * a non-main statement could be on a sub-entity like a Form
	 *
	 * Todo: include references and qualifiers
	 *
	 * @param {string} property
	 * @param {string} value
	 */
	addMainStatement( property, value ) {
		var self = this;
		this.addMainStatementLink.waitForVisible();
		this.addMainStatementLink.click();

		this.mainStatementsContainer.$( this.constructor.STATEMENT_WIDGET_SELECTORS.EDIT_INPUT_PROPERTY ).setValue( property );
		this.mainStatementsContainer.$( this.constructor.STATEMENT_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).waitForVisible();
		this.mainStatementsContainer.$( this.constructor.STATEMENT_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );

		this.mainStatementsContainer.$( this.constructor.TOOLBAR_WIDGET_SELECTORS.SAVE_BUTTON ).waitUntil( function () {
			return self.mainStatementsContainer.$( self.constructor.TOOLBAR_WIDGET_SELECTORS.SAVE_BUTTON ).getAttribute( 'aria-disabled' ) === 'false';
		} );
		this.mainStatementsContainer.$( this.constructor.TOOLBAR_WIDGET_SELECTORS.SAVE_BUTTON ).click();

		this.mainStatementsContainer.$( this.constructor.STATEMENT_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).waitForExist( null, true );
	}

	/**
	 * Get data of the nth statement of a statementGroup on a page
	 *
	 * Todo: include other data e.g. references and qualifiers
	 *
	 * @param {int} index
	 * @param {string} propertyId
	 * @return {{value}}
	 */
	getNthStatementDataFromMainStatementGroup( index, propertyId ) {
		let statementGroup = $( '#' + propertyId ),
			statements = statementGroup.$$( '.wikibase-statementview' ),
			statement = statements[ index ];

		return {
			value: statement.$( this.constructor.STATEMENT_WIDGET_SELECTORS.STATEMENT_VALUE ).getText()
		};
	}

};

module.exports = MainStatementSection;
