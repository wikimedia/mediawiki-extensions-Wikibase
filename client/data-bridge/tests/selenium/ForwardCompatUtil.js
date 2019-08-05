/* eslint-disable */
module.exports = {
	/**
	 * @deprecated Use Util.waitForModuleState() once it is released with webdriverio 5. compatiblity
	 * This is an adjusted copy of Util.waitForModuleState() from wdio-mediawiki.
	 * It has been adjusted for compatibility with webdriverio version 5 by replacing
	 * ```
	 * return result.value;
	 * ```
	 * with
	 * ```
	 * return result;
	 * ```
	 *
	 * Wait for a given module to reach a specific state
	 * @param {string} moduleName The name of the module to wait for
	 * @param {string} moduleStatus 'registered', 'loaded', 'loading', 'ready', 'error', 'missing'
	 * @param {int} timeout The wait time in milliseconds before the wait fails
	 */
	waitForModuleState( moduleName, moduleStatus = 'ready', timeout = 2000 ) {
		browser.waitUntil( () => {
			const result = browser.execute( ( module ) => {
				return typeof mw !== 'undefined' &&
					mw.loader.getState( module.name ) === module.status;
			}, { status: moduleStatus, name: moduleName } );
			return result;
		}, timeout, 'Failed to wait for ' + moduleName + ' to be ' + moduleStatus + ' after ' + timeout + ' ms.' );
	}
};
