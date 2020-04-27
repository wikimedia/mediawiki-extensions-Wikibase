module.exports = {
	disableNetwork() {
		browser.setNetworkConditions( { latency: 0, throughput: 0, offline: true } );
	},

	enableNetwork() {
		/*
		 * This call should be equivalent to calling
		 *     browser.deleteNetworkConditions();
		 * see https://webdriver.io/docs/api/chromium.html#deletenetworkconditions
		 * However, using deleteNetworkConditions during teardown causes the following tests to fail,
		 * whereas using setNetworkConditions doesn't.
		*/
		browser.setNetworkConditions( {}, 'No throttling' );
	},
};
