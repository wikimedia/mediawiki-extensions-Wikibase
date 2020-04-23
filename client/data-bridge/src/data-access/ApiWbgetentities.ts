import ApiErrors from '@/data-access/error/ApiErrors';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import {
	ApiWbgetentitiesResponse,
	PartialEntity,
} from '@/definitions/data-access/ApiWbgetentities';

/*
 * Typical usage of these functions:
 *
 * const response = await this.api.get( {
 *     action: 'wbgetentities',
 *     ids: new Set( [ entityId ] ),
 *     // ...
 * } ).catch( convertNoSuchEntityError );
 * const entity = getApiEntity( response, entityId ) as EntityWith...;
 */

export function getApiEntity( response: ApiWbgetentitiesResponse, entityId: string ): PartialEntity {
	if ( typeof response.entities !== 'object' ) {
		throw new TechnicalProblem( 'Result not well formed.' );
	}
	const entity = response.entities[ entityId ];
	if ( !entity ) {
		throw new EntityNotFound( 'Result does not contain relevant entity.' );
	}
	if ( 'missing' in entity ) {
		throw new EntityNotFound( 'Entity flagged missing in response.' );
	}
	return entity as PartialEntity;
}

export function convertNoSuchEntityError( error: Error ): never {
	if (
		error instanceof ApiErrors &&
		error.errors.length === 1 &&
		error.errors[ 0 ].code === 'no-such-entity'
	) {
		throw new EntityNotFound( 'Entity flagged missing in response.' );
	} else {
		throw error;
	}
}
