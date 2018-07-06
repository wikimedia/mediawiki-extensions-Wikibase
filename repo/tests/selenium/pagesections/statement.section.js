'use strict';

let StatementSection = Base => class extends Base {

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

	get statementsContainer() {
		return $( '.wikibase-statementgrouplistview' );
	}

	get addStatementLink() {
		return $( '.wikibase-statementgrouplistview > div.wikibase-addtoolbar > span > a' );
	}

	/**
	 * Add a statement
	 *
	 * Todo: include references and qualifiers
	 *
	 * @param {string} property
	 * @param {string} value
	 */
	addStatement( property, value ) {
		var self = this;
		this.addStatementLink.waitForVisible();
		this.addStatementLink.click();

		this.statementsContainer.$( this.constructor.STATEMENT_WIDGET_SELECTORS.EDIT_INPUT_PROPERTY ).setValue( property );
		this.statementsContainer.$( this.constructor.STATEMENT_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).waitForVisible();
		this.statementsContainer.$( this.constructor.STATEMENT_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).setValue( value );

		this.statementsContainer.$( this.constructor.TOOLBAR_WIDGET_SELECTORS.SAVE_BUTTON ).waitUntil( function () {
			return self.statementsContainer.$( self.constructor.TOOLBAR_WIDGET_SELECTORS.SAVE_BUTTON ).getAttribute( 'aria-disabled' ) === 'false';
		} );
		this.statementsContainer.$( this.constructor.TOOLBAR_WIDGET_SELECTORS.SAVE_BUTTON ).click();

		this.statementsContainer.$( this.constructor.STATEMENT_WIDGET_SELECTORS.EDIT_INPUT_VALUE ).waitForExist( null, true );
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
	getNthStatementDataFromStatementGroup( index, propertyId ) {
		let statementGroup = $( '#' + propertyId ),
			statements = statementGroup.$$( '.wikibase-statementview' ),
			statement = statements[ index ];

		return {
			value: statement.$( this.constructor.STATEMENT_WIDGET_SELECTORS.STATEMENT_VALUE ).getText()
		};
	}

};

module.exports = StatementSection;
