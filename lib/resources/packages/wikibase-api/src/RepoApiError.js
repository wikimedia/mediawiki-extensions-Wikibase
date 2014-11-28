( function( mw, wb, util ) {
	'use strict';

var MODULE = wb.api;
var PARENT = Error;

/**
 * Wikibase Repo API Error.
 * @class wikibase.api.RepoApiError
 * @extends Error
 * @since 1.0
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 *
 * @constructor
 *
 * @param {string} code Error code (used to determine the actual error message).
 * @param {string} detailedMessage Detailed error information.
 * @param {string} [action] Generic API action (e.g. "save" or "cancel") used to determine a
 *        specific message.
 */
var SELF = MODULE.RepoApiError
	= util.inherit( 'WbRepoApiError', PARENT, function( code, detailedMessage, action ) {
		this.code = code;
		this.detailedMessage = detailedMessage;
		this.action = action;

		// native Error attributes
		this.name = 'Wikibase Repo API Error';
		this.message = this.getMessage();
	},
{
	/**
	 * Message keys of API related error messages.
	 * @property {Object}
	 * @private
	 * @readonly
	 */
	API_ERROR_MESSAGE: {
		GENERIC: {
			DEFAULT: 'wikibase-error-unexpected',
			save: 'wikibase-error-save-generic',
			remove: 'wikibase-error-remove-generic'
		},
		timeout: {
			save: 'wikibase-error-save-timeout',
			remove: 'wikibase-error-remove-timeout'
		},
		'no-external-page': 'wikibase-error-ui-no-external-page',
		'edit-conflict': 'wikibase-error-ui-edit-conflict'
	},

	/**
	 * Returns a short message string.
	 *
	 * @return {string}
	 */
	getMessage: function() {
		var msgKey = this.API_ERROR_MESSAGE[this.code];

		if ( !msgKey || typeof msgKey !== 'string' ) {
			if ( msgKey && this.action && msgKey[this.action] ) {
				msgKey = msgKey[this.action];
			} else if ( this.action && this.API_ERROR_MESSAGE.GENERIC[this.action] ) {
				msgKey = this.API_ERROR_MESSAGE.GENERIC[this.action];
			} else {
				msgKey = this.API_ERROR_MESSAGE.GENERIC.DEFAULT;
			}
		}

		return mw.msg( msgKey );
	}

} );

/**
 * Creates a new RepoApiError out of the values returned from the API.
 * @static
 *
 * @param {Object} details Object returned from the API containing detailed information.
 * @param {string} [apiAction] API action (e.g. 'save', 'remove') that may be passed to
 *        determine a specific message.
 * @return {wikibase.api.RepoApiError}
 */
SELF.newFromApiResponse = function( details, apiAction ) {
	var errorCode = '',
		detailedMessage = '';

	if ( details.error ) {
		errorCode = details.error.code;
		if( details.error.messages ) {
			// HTML message from Wikibase API.
			detailedMessage = messagesObjectToHtml( details.error.messages );
		} else {
			// Wikibase API no-HTML error message fall-back.
			detailedMessage = details.error.info;
		}
	} else if ( details.exception ) {
		// Failed MediaWiki API call.
		errorCode = details.textStatus;
		detailedMessage = details.exception;
	}

	return new SELF( errorCode, detailedMessage, apiAction );
};

/**
 * @ignore
 *
 * @param {Object} messages Object returned from the API.
 * @return {string|undefined} HTML list, single message or undefined if no HTML could be found.
 */
function messagesObjectToHtml( messages ) {
	// Can't use length, it's not an array!
	if ( messages[1] && messages[1].html ) {
		var html = '<ul>';
		for ( var i = 0; messages[i]; i++ ) {
			html += '<li>' + messages[i].html['*'] + '</li>';
		}
		return html + '</ul>';
	}

	return messages[0] && messages[0].html && messages[0].html['*']
		|| messages.html && messages.html['*'];
}

}( mediaWiki, wikibase, util ) );
