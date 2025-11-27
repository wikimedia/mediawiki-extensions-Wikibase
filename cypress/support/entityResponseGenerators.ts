interface SnakOptions {
	property: string;
	value: string;
	hash: string;
	datatype: string;
	snaktype?: 'value' | 'novalue' | 'somevalue';
}

interface Snak {
	snaktype: 'value' | 'novalue' | 'somevalue';
	property: string;
	hash: string;
	datavalue: {
		value: string;
		type: string;
	};
	datatype: string;
}

interface ClaimOptions {
	itemId: string;
	property: string;
	value: string;
	hash: string;
	datatype: string;
	statementId: string;
	snaktype?: 'value' | 'novalue' | 'somevalue';
	rank?: 'normal' | 'preferred' | 'deprecated';
	references?: object[];
}

interface Claim {
	mainsnak: Snak;
	type: string;
	id: string;
	rank: string;
	references?: object[];
}

interface Statement {
	value: string;
	hash: string;
	statementId: string;
	references?: object[];
}

interface EntityResponseOptions {
	itemId: string;
	propertyId: string;
	datatype: 'tabular-data' | 'geo-shape' | 'string';
	statements: Statement[];
	lastrevid?: number;
}

interface EntityResponse {
	entity: {
		type: string;
		id: string;
		labels: Record<string, unknown>;
		descriptions: Record<string, unknown>;
		aliases: Record<string, unknown>;
		claims: Record<string, Claim[]>;
		sitelinks: Record<string, unknown>;
		lastrevid: number;
	};
	success: number;
}

let nextHash = 1;
/**
 * Generates a snak object for Wikibase API responses
 *
 * @param options - Snak configuration
 * @returns snak object
 */
export function generateSnak( options: SnakOptions ): Snak {
	const { property, value, hash, datatype, snaktype = 'value' } = options;

	let hashValue = hash;
	if ( !hashValue ) {
		hashValue = 'hash' + nextHash++;
	}
	return {
		snaktype,
		property,
		hash: hashValue,
		datavalue: {
			value,
			type: 'string',
		},
		datatype,
	};
}

/**
 * Generates a claim/statement object for Wikibase API responses
 *
 * @param options - Claim configuration
 * @returns A claim object
 */
export function generateClaim( options: ClaimOptions ): Claim {
	const { itemId, statementId, rank = 'normal', property, value, hash, datatype, snaktype, references } = options;

	return {
		mainsnak: generateSnak( { property, value, hash, datatype, snaktype } ),
		type: 'statement',
		id: `${ itemId }$${ statementId }`,
		rank,
		references,
	};
}

/**
 * Generates a Wikibase entity response for the wbeditentity API
 *
 * @param options - Entity configuration
 * @returns Wikibase entity response object
 */
export function generateEntityResponse( options: EntityResponseOptions ): EntityResponse {
	const { itemId, propertyId, datatype, statements, lastrevid = 12345 } = options;

	const claims = statements.map( ( stmt ) => generateClaim( {
		itemId,
		property: propertyId,
		value: stmt.value,
		hash: stmt.hash,
		datatype,
		statementId: stmt.statementId,
		references: stmt.references,
	} ) );

	return {
		entity: {
			type: 'item',
			id: itemId,
			labels: {},
			descriptions: {},
			aliases: {},
			claims: {
				[ propertyId ]: claims,
			},
			sitelinks: {},
			lastrevid,
		},
		success: 1,
	};
}

export type { SnakOptions, ClaimOptions, Statement, EntityResponseOptions, Snak, Claim, EntityResponse };
