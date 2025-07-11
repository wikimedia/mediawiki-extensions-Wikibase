'use strict';

exports.mochaHooks = {
	beforeAll() {
		// Skip all search tests in CI if OpenSearch is not available
		if ( process.env.QUIBBLE_OPENSEARCH && process.env.QUIBBLE_OPENSEARCH !== 'true' ) {
			this.skip();
		}
	}
};
