// Utility functions shared across the wbui2025 within Vue templates.
// For SSR elements and tempAccount handling. Note that some of these
// duplicate definitions must exist in WMDE\VueJsTemplating\App::methods.
module.exports = exports = {
	concat: ( ...args ) => args.join( '' ),
	implode: ( separator, array ) => array.join( separator ),

	getCurrentPageLocation: () => ( {
		title: mw.config.get( 'wgPageName' ),
		query: location.search.slice( 1 ),
		anchor: location.hash
	} ),

	addReturnToParams: ( params, location ) => {
		if ( !params || typeof params !== 'object' ) {
			throw new TypeError( 'Expected params to be an object.' );
		}

		const result = Object.assign( {}, params );

		if ( location.title ) {
			result.returnto = location.title;
		}
		if ( location.query ) {
			result.returntoquery = location.query;
		}
		if ( location.anchor ) {
			result.returntoanchor = location.anchor;
		}

		return result;
	},

	handleTempUserRedirect: ( response ) => {
		if ( !response.tempuserredirect ) {
			return false;
		}

		location.href = response.tempuserredirect;
		return true;
	},

	/**
	 * Extracts a user-friendly error message from an API error object.
	 *
	 * @param {Object} errorObj The error object from the API wrapper { code, error }
	 * @param {string} fallbackMessage The default message to use if no specific error is found
	 * @returns {string} A user-friendly error message
	 */
	extractErrorMessage: ( errorObj, fallbackMessage ) => {
		if ( !errorObj || !errorObj.errorData ) {
			return fallbackMessage;
		}

		const apiError = errorObj.errorData;

		if ( apiError.errors && Array.isArray( apiError.errors ) && apiError.errors.length > 0 ) {
			const firstError = apiError.errors[ 0 ];

			if ( firstError.text ) {
				return mw.msg( 'wikibase-error-save-generic' ) + '\n' + firstError.text;
			}
		}

		return fallbackMessage;
	}
};
