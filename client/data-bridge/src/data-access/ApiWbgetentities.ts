import ApiErrors from '@/data-access/error/ApiErrors';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import DataType from '@/datamodel/DataType';
import {
	ApiResponseEntity,
	ApiWbgetentitiesResponse,
} from '@/definitions/data-access/Api';

export interface PartialEntity extends ApiResponseEntity {
	type: string;
}

export interface EntityWithDataType extends PartialEntity {
	datatype: DataType;
}

export interface EntityWithLabels extends PartialEntity {
	labels: {
		[ lang: string ]: {
			language: string;
			value: string;
			'for-language'?: string;
		};
	};
}

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
