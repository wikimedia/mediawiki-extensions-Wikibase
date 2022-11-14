( function ( mw ) {
	'use strict';

	function fetchResults( q, batchSize, offset = null ) {
		const api = new mw.Api();
		const data = {
			action: 'wbsearchentities',
			search: q,
			limit: batchSize,
			format: 'json',
			errorformat: 'plaintext',
			language: mw.config.get( 'wgUserLanguage' ),
			uselang: mw.config.get( 'wgUserLanguage' ),
			type: 'item'
		};
		if ( offset ) {
			data.continue = offset;
		}
		const getJson = api.get( data );

		function getMatchText( { type, text } ) {
			if ( type === 'alias' || type === 'entityId' ) {
				return text;
			}

			return '';
		}

		const searchResponsePromise = getJson.then( ( res ) => {
			return {
				query: q,
				results: res.search.map( ( { id, label, url, match, description, display = {} } ) => ( {
					value: id,
					label,
					match: getMatchText( match ),
					description,
					url,
					language: {
						label: display.label ? display.label.language : undefined,
						match: match.type === 'alias' ? match.language : undefined,
						description: display.description ? display.description.language : undefined
					}
				} ) )
			};
		} );

		return {
			fetch: searchResponsePromise,
			abort: () => {
				api.abort();
			}
		};
	}

	const vectorSearchClient = {
		fetchByTitle: ( q, limit = 10, _showDescription = true ) => {
			return fetchResults( q, limit );
		},
		loadMore: ( q, offset, limit = 10, _showDescription = true ) => {
			return fetchResults( q, limit, offset );
		}
	};

	mw.config.set( 'wgVectorSearchClient', vectorSearchClient );
}( mw ) );
