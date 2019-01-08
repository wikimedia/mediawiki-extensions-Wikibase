'use strict';

let ComponentInteraction = ( Base ) => class extends Base {

	static get OOUI_SELECTORS() {
		return {
			OPTION_WIDGET: '.oo-ui-optionWidget',
			OPTION_WIDGET_SELECTED: '.oo-ui-optionWidget-selected',
			OVERLAY: '.oo-ui-defaultOverlay',
			COMBOBOX_DROPDOWN: '.oo-ui-comboBoxInputWidget-dropdownButton'
		};
	}

	setValueOnLookupElement( element, value ) {
		element.$( 'input' ).setValue( value );
		element.waitForVisible( this.constructor.OOUI_SELECTORS.OPTION_WIDGET );
		element.$( this.constructor.OOUI_SELECTORS.OPTION_WIDGET ).click();
	}

	setValueOnComboboxElement( element, value ) {
		element.$( 'input' ).setValue( value );
		browser.waitUntil( () => {
			return (
				browser.isVisible(
					this.constructor.OOUI_SELECTORS.OVERLAY +
					' ' +
					this.constructor.OOUI_SELECTORS.OPTION_WIDGET_SELECTED
				)
			);
		} );
		// close suggestion overlay
		element.$( this.constructor.OOUI_SELECTORS.COMBOBOX_DROPDOWN ).click();
	}
};

module.exports = ComponentInteraction;
