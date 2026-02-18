const apiOptions = {
	parameters: {
		formatversion: 2,
		errorformat: 'plaintext',
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
	constructor( errorCode, errorMessage, errorData ) {
		this.errorCode = errorCode;
		this.errorMessage = errorMessage;
		this.errorData = errorData;
	}
}

module.exports = {
	ErrorObject,
	api,
	foreignApi
};
