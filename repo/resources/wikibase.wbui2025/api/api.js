const apiOptions = {
	parameters: {
		formatversion: 2,
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

function assertCurrentUserUnlessAnon( params ) {
	return mw.config.get( 'wgUserName' ) === null ? params : api.assertCurrentUser( params );
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
	assertCurrentUserUnlessAnon,
	foreignApi
};
