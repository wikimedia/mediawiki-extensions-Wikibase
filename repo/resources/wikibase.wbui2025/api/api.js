const apiOptions = {
	parameters: {
		errorformat: 'html',
		uselang: mw.config.get( 'wgUserLanguage' )
	}
};

const api = new mw.Api( apiOptions );

function foreignApi( apiUrl ) {
	return new mw.ForeignApi( apiUrl, Object.assign( {
		anonymous: true
	}, apiOptions ) );
}

class ErrorObject {
	constructor( errorCode, $errorHtml, errorData ) {
		this.errorCode = errorCode;
		this.$errorHtml = $errorHtml;
		this.errorData = errorData;
	}
}

module.exports = {
	ErrorObject,
	api,
	foreignApi
};
