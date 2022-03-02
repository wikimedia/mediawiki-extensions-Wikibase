import SwaggerUI from 'swagger-ui';
import 'swagger-ui/dist/swagger-ui.css';

import spec from '../specs/openapi.json';

const ui = SwaggerUI( {
	spec,
	dom_id: '#swagger' // eslint-disable-line camelcase
} );

ui.initOAuth( {
	appName: 'Wikibase REST API',
	// See https://demo.identityserver.io/ for configuration details.
	clientId: 'implicit'
} );
