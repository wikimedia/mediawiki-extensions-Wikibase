/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb, util ) {
	'use strict';

	var PARENT = Error;

	/**
	 * Wikibase Repo API Error.
	 *
	 * @constructor
	 * @extends Error
	 * @since 0.4
	 *
	 * @param {string} code Error code (used to determine the actual error message)
	 * @param {string} detailedMessage Detailed error information
	 * @param {string} [action] Generic API action (e.g. "save" or "cancel") used to determine a
	 *        specific message
	 */
	var constructor = function( code, detailedMessage, action ) {
		this.code = code;
		this.detailedMessage = detailedMessage;
		this.action = action;

		// native Error attributes
		this.name = 'Wikibase Repo API Error';
		this.message = this.getMessage();
	};

	wb.RepoApiError = util.inherit( 'WbRepoApiError', PARENT, constructor,
		{
			/**
			 * Message keys of API related error messages.
			 * @type {Object}
			 * @constant
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
				'client-error': 'wikibase-error-ui-client-error',
				'no-external-page': 'wikibase-error-ui-no-external-page',
				'cant-edit': 'wikibase-error-ui-cant-edit',
				'no-permissions': 'wikibase-error-ui-no-permissions',
				'link-exists': 'wikibase-error-ui-link-exists',
				'session-failure': 'wikibase-error-ui-session-failure',
				'edit-conflict': 'wikibase-error-ui-edit-conflict',
				'patch-incomplete': 'wikibase-error-ui-edit-conflict'
			},

			/**
			 * Gets a short message string.
			 * @since 0.4
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

		}
	);

	/**
	 * Creates a new RepoApiError out of the values returned from the API.
	 * @since 0.4
	 *
	 * @param {Object} details Object returned from the API containing detailed information
	 * @param {string} [apiAction] API action (e.g. 'save', 'remove') that may be passed to
	 *        determine a specific message
	 * @return {wikibase.RepoApiError}
	 */
	wb.RepoApiError.newFromApiResponse = function( details, apiAction ) {
		var errorCode = '',
			detailedMessage = '';

		if ( details.error ) {
			errorCode = details.error.code;
			detailedMessage = details.error.messages
				&& messagesObjectToHtml( details.error.messages ) || details.error.info;
		} else if ( details.exception ) {
			errorCode = details.textStatus;
			detailedMessage = details.exception;
		}

		return new wb.RepoApiError( errorCode, detailedMessage, apiAction );
	};

	/**
	 * @param {Object} messages Object returned from the API
	 * @return {string|undefined} HTML list, single message or undefined if no HTML could be found
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
