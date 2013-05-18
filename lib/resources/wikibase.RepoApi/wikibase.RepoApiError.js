/**
 * @file
 * @ingroup WikibaseLib
 * @licence GNU GPL v2+
 * @since 0.4
 *
 * @author H. Snater < mediawiki@snater.com >
 */
( function( mw, wb ) {
	'use strict';

	var PARENT = Error,

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
		constructor = function( code, detailedMessage, action ) {
			this.code = code;
			this.detailedMessage = detailedMessage;
			this.action = action;

			// native Error attributes
			this.name = 'Wikibase Repo API Error';
			this.message = this.getMessage();
		};

	wb.RepoApiError = wb.utilities.inherit( 'WbRepoApiError', PARENT, constructor,
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
	 * @param {string} errorCode Error code or text status returned from the API
	 * @param {Object} details Object returned from the API containing detailed information
	 * @param {string} [apiAction] API action (e.g. 'save', 'remove') that may be passed to
	 *        determine a specific message
	 * @return {wb.RepoApiError}
	 */
	wb.RepoApiError.newFromApiResponse = function( errorCode, details, apiAction ) {
		var detailedMessage = '';

		if ( details.error ) {
			detailedMessage = details.error.info;
		} else if ( details.exception ) {
			errorCode = details.textStatus;
			detailedMessage = details.exception;
		}

		return new wb.RepoApiError( errorCode, detailedMessage, apiAction );
	};

}( mediaWiki, wikibase ) );
