this.util = this.util || {};

( function( util ) {
	'use strict';

	/**
	 * A service storing information about the languages available for the content.
	 * Uses `util.inherit`.
	 *
	 * @class util.ContentLanguages
	 * @abstract
	 * @uses util
	 * @license GNU GPL v2+
	 * @author Adrian Heine <adrian.heine@wikimedia.de>
	 *
	 * @constructor
	 */
	util.ContentLanguages = function() {
	};

	/**
	 * @class util.ContentLanguages
	 */
	util.ContentLanguages.prototype = {
		/**
		 * Returns all registered language codes or `null` if no information about accepted
		 * languages is registered.
		 *
		 * @return {string[]|null}
		 */
		getAll: util.abstractMember,

		/**
		 * Returns a name for a specific language code. Preferably, the name should be in a language
		 * the user understands (i. e. the UI language). Fall-backs are allowed, though. Returns
		 * `null` if the language code is not registered.
		 *
		 * @param {string} languageCode
		 * @return {string|null}
		 */
		getName: util.abstractMember
	};
}( util ) );
