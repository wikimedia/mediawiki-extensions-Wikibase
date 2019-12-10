import {
	getApiEntity,
	convertNoSuchEntityError,
} from '@/data-access/ApiWbgetentities';
import ApiErrors from '@/data-access/error/ApiErrors';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import { ApiWbgetentitiesResponse } from '@/definitions/data-access/ApiWbgetentities';
import jqXHR = JQuery.jqXHR;

describe( 'apiEntity', () => {

	it( 'rejects on result missing entities key', () => {
		return expect( () => getApiEntity( {} as ApiWbgetentitiesResponse, 'Q1' ) )
			.toThrow( new TechnicalProblem( 'Result not well formed.' ) );
	} );

	it( 'rejects on result missing relevant entity in entities', () => {
		const absentId = 'Q4';
		const presentId = 'Q42';
		const response: ApiWbgetentitiesResponse = { entities: { [ presentId ]: { id: presentId } } };

		return expect( () => getApiEntity( response, absentId ) )
			.toThrow( new TechnicalProblem( 'Result does not contain relevant entity.' ) );
	} );

	it( 'rejects on result indicating relevant entity as missing (via missing)', () => {
		const id = 'Q4';
		const response: ApiWbgetentitiesResponse = { entities: { [ id ]: { id, missing: '' } } };

		return expect( () => getApiEntity( response, id ) )
			.toThrow( new EntityNotFound( 'Entity flagged missing in response.' ) );
	} );

} );

describe( 'catchNoSuchEntityError', () => {

	it( 'turns no-such-entity error into EntityNotFound', () => {
		const error = new ApiErrors( [ { code: 'no-such-entity' } ] );
		return expect( () => convertNoSuchEntityError( error ) )
			.toThrow( new EntityNotFound( 'Entity flagged missing in response.' ) );
	} );

	it( 'preserves other API errors', () => {
		const error = new ApiErrors( [ { code: 'unknown_action' } ] );
		return expect( () => convertNoSuchEntityError( error ) )
			.toThrow( error );
	} );

	it( 'preserves other non-API errors', () => {
		const error = new JQueryTechnicalError( {} as jqXHR );
		return expect( () => convertNoSuchEntityError( error ) )
			.toThrow( error );
	} );

} );
