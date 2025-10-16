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

module.exports = {
	api,
	foreignApi
};
