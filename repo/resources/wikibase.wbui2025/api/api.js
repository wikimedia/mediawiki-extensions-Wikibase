const api = new mw.Api( {
	parameters: {
		errorformat: 'html',
		uselang: mw.config.get( 'wgUserLanguage' )
	}
} );

module.exports = {
	api
};
