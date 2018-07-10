'use strict';

let InputInteraction = Base => class extends Base {

	static get OOUI_SELECTORS() {
		return {
			OPTION_WIDGET: '.oo-ui-optionWidget'
		};
	}

	setValueOnLookupElement( element, value ) {
		element.$( 'input' ).setValue( value );
		element.waitForVisible( this.constructor.OOUI_SELECTORS.OPTION_WIDGET );
		element.$( this.constructor.OOUI_SELECTORS.OPTION_WIDGET ).click();
	}
};

module.exports = InputInteraction;
