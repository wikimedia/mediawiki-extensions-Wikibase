import SwaggerUI from 'swagger-ui';
import 'swagger-ui/dist/swagger-ui.css';

const ui = SwaggerUI( {
	// Injected at build time; see webpack.config.js for how the documented
	// wiki is chosen.
	url: process.env.OPENAPI_DOC_URL,
	dom_id: '#swagger', // eslint-disable-line camelcase
	deepLinking: true,
	showCommonExtensions: true,
	supportedSubmitMethods: []
} );

ui.initOAuth( {
	appName: 'Wikibase REST API',
	// See https://demo.identityserver.io/ for configuration details.
	clientId: 'implicit'
} );
