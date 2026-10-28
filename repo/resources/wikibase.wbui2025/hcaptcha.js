const {
	extractCaptchaData,
	getCaptchaIfRequired,
	resolveCaptchaAndResubmit,
	isCaptchaRequired
} = require( 'wikibase.captcha' );

const privacyPolicyHtml = mw.config.exists( 'wbHCaptchaPrivacyPolicyHtml' ) ?
	mw.config.get( 'wbHCaptchaPrivacyPolicyHtml' ) : '';

module.exports = {
	extractCaptchaData,
	getCaptchaIfRequired,
	resolveCaptchaAndResubmit,
	isCaptchaRequired,
	privacyPolicyHtml
};
