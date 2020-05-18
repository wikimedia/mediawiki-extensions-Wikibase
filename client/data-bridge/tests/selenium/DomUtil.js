module.exports = {
	/**
	 * Set the value of an element to the given value,
	 * and ensure that it actually is that value before returning.
	 * (For some reason, WebdriverIO’s own setValue sometimes fails.)
	 */
	setValue( element, value ) {
		browser.waitUntil( () => {
			element.setValue( value );
			return element.getValue() === value;
		} );
	},
};
