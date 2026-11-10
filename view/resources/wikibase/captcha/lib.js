const resolve = require( './resolveCaptcha.js' );
const post = require( './postCaptcha.js' );

module.exports = {
	isCaptchaRequired: resolve.isCaptchaRequired,
	extractCaptchaData: resolve.extractCaptchaData,
	getCaptchaIfRequired: resolve.getCaptchaIfRequired,
	resolveCaptchaAndResubmit: resolve.resolveCaptchaAndResubmit,
	withCaptcha: post.withCaptcha
};
