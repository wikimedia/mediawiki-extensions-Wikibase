'use strict';

let ComponentInteraction = ( Base ) => class extends Base {

	static get OOUI_SELECTORS() {
		return {
			LOOKUP_OPTION_WIDGET: '.oo-ui-lookupElement-menu .oo-ui-optionWidget',
			MULTI_OPTION_WIDGET: '.oo-ui-optionWidget',
			OPTION_WIDGET_SELECTED: '.oo-ui-optionWidget-selected',
			OVERLAY: '.oo-ui-defaultOverlay',
			COMBOBOX_DROPDOWN: '.oo-ui-comboBoxInputWidget-dropdownButton'
		};
	}

	setValueOnLookupElement( element, value ) {
		element.$( 'input' ).setValue( value );
		$( 'body' ).waitForVisible( this.constructor.OOUI_SELECTORS.LOOKUP_OPTION_WIDGET );
		$( this.constructor.OOUI_SELECTORS.LOOKUP_OPTION_WIDGET ).click();
	}

	setSingleValueOnMultiselectElement( element, value ) {
		element.$( 'input' ).setValue( value );
		element.waitForVisible( this.constructor.OOUI_SELECTORS.MULTI_OPTION_WIDGET );
		element.$( this.constructor.OOUI_SELECTORS.MULTI_OPTION_WIDGET ).click();
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
