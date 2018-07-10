'use strict';

let FormWidgets = Base => class extends Base {

	static get OOUI_OPTION_WIDGET_SELECTORS() {
		return {
			OPTION: '.oo-ui-optionWidget'
		};
	}

	setValueOnOptionWidget( element, value ) {
		element.$( 'input' ).setValue( value );
		element.waitForVisible( this.constructor.OOUI_OPTION_WIDGET_SELECTORS.OPTION );
		element.$( this.constructor.OOUI_OPTION_WIDGET_SELECTORS.OPTION ).click();
	}
};

module.exports = FormWidgets;
