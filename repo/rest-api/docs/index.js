import SwaggerUI from 'swagger-ui';
import 'swagger-ui/dist/swagger-ui.css';

import spec from '../src/openapi.json';

const ui = SwaggerUI( {
	spec,
	dom_id: '#swagger', // eslint-disable-line camelcase
	deepLinking: true,
	showCommonExtensions: true
} );

ui.initOAuth( {
	appName: 'Wikibase REST API',
	// See https://demo.identityserver.io/ for configuration details.
	clientId: 'implicit'
} );
