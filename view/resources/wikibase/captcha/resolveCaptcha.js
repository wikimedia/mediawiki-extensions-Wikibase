/**
 * Handling for ConfirmEdit CAPTCHAs in the JS entity editors.
 *
 * A CAPTCHA may be required for a "generic" edit — one whose requirement does not depend on
 * the edit content, known from wgConfirmEditCaptchaNeededForGenericEdit — or only once a
 * save is attempted (because of the edit content, or because AbuseFilter asks for a
 * stricter challenge). getCaptchaIfRequired() handles the first case so the first save
 * carries the CAPTCHA response; resolveCaptchaAndResubmit() handles the second.
 *
 * No hard dependency on ConfirmEdit, which is optional: its widget is only loaded and used
 * after checking at runtime that the module is present.
 *
 * This code was substantially co-authored by Claude Opus 4.8 - see I199985c8716f7aa9305ac107eec308fad06e0a3b
 *
 * @license GPL-2.0-or-later
 */

const CAPTCHA_WIDGET_MODULE = 'ext.confirmEdit.CaptchaWidget';

/**
 * Extract the CAPTCHA descriptor from a save-failure response. Its location depends on the
 * errorformat: `error.captcha` (bc), or `errors[N].data.captcha` (html/structured).
 *
 * @param {Object} response
 * @return {Object|null}
 */
function extractCaptchaData( response ) {
	if ( !response ) {
		return null;
	}
	if ( response.error && response.error.captcha ) {
		return response.error.captcha;
	}
	if ( Array.isArray( response.errors ) ) {
		for ( const error of response.errors ) {
			if ( error && error.data && error.data.captcha ) {
				return error.data.captcha;
			}
		}
	}
	return null;
}

/**
 * @param {Object} submission Result of CaptchaWidget.getCaptchaDataForSubmission().
 * @return {Object} API parameters carrying the CAPTCHA response.
 */
function toCaptchaParams( submission ) {
	const params = {
		captchaid: submission.captchaid,
		captchaword: submission.captchaword
	};
	if ( submission.wgConfirmEditForceShowCaptcha ) {
		params.wgConfirmEditForceShowCaptcha = submission.wgConfirmEditForceShowCaptcha;
	}
	return params;
}

/**
 * Render a CAPTCHA into a temporary container, removed once the callback settles.
 *
 * @param {Function} callback Receives the container element; returns a Promise.
 * @return {Promise}
 */
function withContainer( callback ) {
	const container = document.body.appendChild( document.createElement( 'div' ) );
	return Promise.resolve().then( () => callback( container ).then( ( res ) => {
		container.remove();
		return res;
	} ) ).catch( ( e ) => {
		container.remove();
		throw e;
	} );
}

/**
 * `true` if the resource loader module for ConfirmEdit's captcha widget is
 * not defined or there was an error loading it. This indicates to us that the
 * ConfirmEdit extension is not loaded or loading the extension has somehow failed.
 *
 * @returns {boolean}
 */
function isConfirmEditResourceLoaderModuleMissing() {
	return [ 'missing', 'error', null ].includes( mw.loader.getState( CAPTCHA_WIDGET_MODULE ) );
}

/**
 * Returns the configured captcha plugin for captchas on generic edits, or `null`
 * if none is defined
 *
 * @returns {string|null}
 */
function getCaptchaConfigurationForGenericEdit() {
	return mw.config.get( 'wgConfirmEditCaptchaNeededForGenericEdit' );
}

/**
 * Returns true if ConfirmEdit is loaded and CAPTCHAs are required for edit actions, i.e. true if
 * data will be sent to a captcha server.
 *
 * @returns {boolean}
 */
function isCaptchaRequired() {
	if ( isConfirmEditResourceLoaderModuleMissing() ) {
		return false;
	}
	return !!getCaptchaConfigurationForGenericEdit();
}

/**
 * Render the CAPTCHA required for a "generic" edit — one that does not depend on the edit
 * content — and return its response, so the first save carries it, as VisualEditor does.
 * Resolves null when no such CAPTCHA is required or ConfirmEdit is unavailable.
 *
 * @param {Object} [options]
 * @param {string} [options.interfaceName]
 * @return {Promise<Object|null>}
 */
function getCaptchaIfRequired( options ) {
	if ( !isCaptchaRequired() ) {
		return Promise.resolve( null );
	}
	const type = getCaptchaConfigurationForGenericEdit();

	return withContainer( ( container ) => mw.loader.using( CAPTCHA_WIDGET_MODULE ).then( () => {
		const widget = new mw.libs.confirmEdit.CaptchaWidget( {
			container: container,
			type: type,
			interfaceName: ( options && options.interfaceName ) || 'unknown'
		} );
		// We create an anonymous element at the end of the document body for hCaptcha and tidy it up afterwards,
		// but hCaptcha creates its own <iframe> elements to display the captchas themselves and we have no reference
		// to these or way to clear them up.
		// TODO: T443876 Clear up the <iframes> left by hCaptcha
		return widget.renderCaptcha()
			.then( () => widget.getCaptchaDataForSubmission() )
			.then( toCaptchaParams );
	} ) );
}

/**
 * Render the CAPTCHA that blocked a save, collect its response, and submit again with it
 * added. If the server then asks for a stricter challenge, one more attempt is made; any
 * further CAPTCHA error is returned to the caller (as VisualEditor does) rather than
 * trying forever. Rejects (leaving the original error for the caller) if ConfirmEdit is
 * unavailable.
 *
 * @param {Object} originalParams The blocked request, resubmitted with the CAPTCHA merged in.
 * @param {Object} options
 * @param {Object} options.captchaData The `captcha` descriptor from the failed response.
 * @param {string} options.captchaData.captchaid The captcha ID field to submit, may be empty for some CAPTCHAs.
 * @param {string} options.captchaData.captchaword The captcha word field to submit. Depending on the CAPTCHA type,
 *   this may be user-entered data or a token generated by the CAPTCHA.
 * @param {boolean} options.captchaData.[wgConfirmEditForceShowCaptcha] An optional field, appended to
 *   indicate that the CAPTCHA challenge solved was the one force-shown. For example,
 *   set when the user matches an AbuseFilter filter with the "showcaptcha" consequence.
 * @param {string} [options.interfaceName]
 * @return {Promise<Object>}
 */
function resolveCaptchaAndResubmit( originalParams, options ) {
	const api = new mw.Api();
	if ( isConfirmEditResourceLoaderModuleMissing() ) {
		return Promise.reject( new Error( 'ConfirmEdit is not available' ) );
	}

	return withContainer( ( container ) => mw.loader.using( CAPTCHA_WIDGET_MODULE ).then( () => {
		const widget = new mw.libs.confirmEdit.CaptchaWidget( {
			container: container,
			interfaceName: options.interfaceName || 'unknown'
		} );
		let hasAlreadyResubmitted = false;

		function attempt( captchaData ) {
			return widget.updateForFailure( captchaData )
				.then( () => widget.renderCaptcha() )
				.then( () => widget.getCaptchaDataForSubmission() )
				.then( ( submission ) => {
					const resubmitParams = Object.assign( {}, originalParams, toCaptchaParams( submission ) );
					const request = api.postWithEditToken( api.assertCurrentUser( resubmitParams ) );
					return request.then( null, ( code, data ) => {
						const nextCaptchaData = extractCaptchaData( data );
						if ( nextCaptchaData && !hasAlreadyResubmitted ) {
							hasAlreadyResubmitted = true;
							return attempt( nextCaptchaData );
						}
						// A native promise keeps only the first rejection argument
						throw data || new Error( code );
					} );
				} );
		}

		return attempt( options.captchaData );
	} ) );
}

module.exports = {
	isCaptchaRequired,
	extractCaptchaData,
	getCaptchaIfRequired,
	resolveCaptchaAndResubmit
};
