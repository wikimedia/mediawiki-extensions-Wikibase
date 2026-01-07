import { generateEntityResponse, type EntityResponseOptions } from './entityResponseGenerators';

interface CommonsSearchResult {
	ns: number;
	title: string;
	pageid: number;
	size: number;
	wordcount: number;
	snippet: string;
	timestamp: string;
}

interface CommonsSearchOptions {
	totalhits?: number;
	results: CommonsSearchResult[];
	hasContinue?: boolean;
}

interface CommonsSearchResponse {
	batchcomplete: string;
	query: {
		searchinfo: { totalhits: number };
		search: CommonsSearchResult[];
	};
	continue?: {
		sroffset: number;
		continue: string;
	};
}

type ItemLabelMap = {
	[key: string]: string;
};

/**
 * Creates a mock intercept for the Commons search API
 *
 * @param options - Configuration for the mock response
 * @param alias - Cypress alias for the intercept (default: 'commonsSearch')
 */
export function interceptCommonsSearch( options: CommonsSearchOptions, alias: string = 'commonsSearch' ): void {
	const { totalhits = options.results.length, results } = options;

	const body: CommonsSearchResponse = {
		batchcomplete: '',
		query: {
			searchinfo: { totalhits },
			search: results,
		},
	};

	cy.intercept( 'GET', 'https://commons.wikimedia.org/w/api.php?*', {
		statusCode: 200,
		body,
	} ).as( alias );
}

/**
 * Creates a mock intercept for the wbformatvalue API that formats data values
 *
 * @param itemLabelMap - mapping from item IDs to labels
 * @param alias - Cypress alias for the intercept (default: 'formatValue')
 */
export function interceptFormatValue( itemLabelMap: ItemLabelMap = {}, alias: string = 'formatValue' ): void {
	cy.intercept( 'GET', '**/api.php?*action=wbformatvalue*', ( req ) => {
		const url = new URL( req.url );
		const datavalueParam = url.searchParams.get( 'datavalue' );

		if ( datavalueParam ) {
			const datavalue = JSON.parse( decodeURIComponent( datavalueParam ) );
			let value = datavalue.value;
			let href = datavalue.value;
			if ( typeof value === 'object' && value.id && itemLabelMap[ value.id ] ) {
				value = itemLabelMap[ value.id ];
				href = value.id;
			}

			req.reply( {
				statusCode: 200,
				body: {
					result: `<a href="/wiki/${ href }">${ value }</a>`,
				},
			} );
		}
	} ).as( alias );
}

/**
 * Creates a mock intercept for the wbeditentity API (save statement)
 *
 * @param options - Configuration for the mock save response
 * @param alias - Cypress alias for the intercept (default: 'saveStatement')
 */
export function interceptSaveEntity( options: EntityResponseOptions, alias: string = 'saveStatement' ): void {
	cy.intercept( 'POST', '**/api.php*', ( req ) => {
		if ( req.body && req.body.includes( 'action=wbeditentity' ) ) {
			req.reply( {
				statusCode: 200,
				body: generateEntityResponse( options ),
			} );
		}
	} ).as( alias );
}
