import { GraphiQL } from 'graphiql';
import 'graphiql/style.css';

function getQueryFromUrlParam() {
	const params = new URLSearchParams( window.location.search );
	return params.get( 'query' );
}

function updateQueryInUrl( query ) {
	const url = new URL( window.location.href );
	url.searchParams.set( 'query', query );
	window.history.replaceState({}, '', url);
}

async function fetcher( graphQLParams ) {
	// when the user submits a query, update the query param in the address bar
	if ( graphQLParams.operationName !== 'IntrospectionQuery' ) {
		updateQueryInUrl( graphQLParams.query );
	}

	const response = await fetch( import.meta.env.VITE_GQL_ENDPOINT_URL, {
		method: 'POST',
		headers: {
			Accept: 'application/json',
			'Content-Type': 'application/json',
		},
		body: JSON.stringify( graphQLParams ),
	} );

	return response.json();
}

function App() {
	return <GraphiQL fetcher={fetcher} initialQuery={getQueryFromUrlParam()}/>;
}

export default App;
