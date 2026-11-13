const captcha = require( 'wikibase.captcha' );

/**
 * Submit classic entity edits using the shared CAPTCHA helper.
 *
 * @license GPL-2.0-or-later
 * @param {mw.Api} api
 * @param {string} tokenType
 * @param {Object} params
 * @return {jQuery.Promise}
 */
function postWithCaptcha( api, tokenType, params ) {
	const deferred = $.Deferred();

	Promise.resolve()
		.then( () => captcha.getCaptchaIfRequired( {
			interfaceName: 'wikibase'
		} ) )
		.then( ( captchaParams ) => {
			const submissionParams = Object.assign( {}, params, captchaParams );

			return api.postWithToken( tokenType, submissionParams ).then(
				( response ) => {
					deferred.resolve( response );
				},
				( code, response ) => {
					const captchaData = captcha.extractCaptchaData( response );

					if ( !captchaData ) {
						deferred.reject( code, response );
						return;
					}

					return captcha.resolveCaptchaAndResubmit(
						submissionParams,
						{
							interfaceName: 'wikibase',
							captchaData: captchaData
						}
					).then( ( result ) => {
						deferred.resolve( result );
					} );
				}
			);
		} )
		.catch( ( error ) => {
			const apiError = error && ( error.error || ( error.errors && error.errors[ 0 ] ) );

			if ( apiError && apiError.code ) {
				deferred.reject( apiError.code, error );
			} else {
				deferred.reject( 'captcha-failed', {
					textStatus: 'captcha-failed',
					exception: error
				} );
			}
		} );

	return deferred.promise();
}

/**
 * Wrap an mw.Api so that RepoApi's writes resolve CAPTCHAs with the shared helper.
 *
 * @param {mw.Api} api
 * @return {{get: Function, postWithToken: Function}}
 */
module.exports = ( api ) => ( {
	get: ( ...args ) => api.get( ...args ),
	postWithToken: ( tokenType, params ) => postWithCaptcha( api, tokenType, params )
} );
