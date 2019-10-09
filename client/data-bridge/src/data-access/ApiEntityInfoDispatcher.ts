import EntityNotFound from '@/data-access/error/EntityNotFound';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import { Api } from '@/@types/mediawiki/MwWindow';
import EntityInfoDispatcher, { WellFormedResponse } from '@/definitions/data-access/EntityInfoDispatcher';

interface ErrorResponse {
	error: {
		code: string;
	};
}

interface RequestParameters {
	props: Set<string>;
	ids: Set<string>;
	otherParams: {
		[ paramKey: string ]: number | string;
	};
}

interface GetAndClearRequestData {
	parameters: RequestParameters;
	resolveCallbacks: Function[];
	rejectCallbacks: Function[];
}

export default class ApiEntityInfoDispatcher implements EntityInfoDispatcher {
	private api: Api;
	private parameters: RequestParameters | null = null;
	private resolveCallbacks: Function[] = [];
	private rejectCallbacks: Function[] = [];
	private waitForProps: string[] = [];

	public constructor( api: Api, waitForProps: string[] = [] ) {
		this.api = api;
		this.waitForProps = waitForProps;
	}

	public dispatchEntitiesInfoRequest( requestData: {
		props: string[];
		ids: string[];
		otherParams?: { [ paramKey: string ]: number | string };
	} ): Promise<WellFormedResponse['entities']> {
		return new Promise<WellFormedResponse['entities']>( ( resolve, reject ) => {
			this.addRequest( requestData );
			this.resolveCallbacks.push( resolve );
			this.rejectCallbacks.push( reject );
			this.executeRequestIfPropsAreComplete();
		} );
	}

	private addRequest( requestData: {
		props: string[];
		ids: string[];
		otherParams?: { [ paramKey: string ]: number | string };
	} ): void {
		if ( this.parameters === null ) {
			this.parameters = {
				props: new Set( [] ),
				ids: new Set( [] ),
				otherParams: {},
			};
		}

		this.parameters.props = new Set( [
			...this.parameters.props,
			...requestData.props,
		] );
		this.parameters.ids = new Set( [
			...this.parameters.ids,
			...requestData.ids,
		] );
		if ( requestData.otherParams ) {
			this.parameters.otherParams = {
				...this.parameters.otherParams,
				...requestData.otherParams,
			};
		}
	}

	private executeRequestIfPropsAreComplete(): void {
		if ( this.waitForProps.every( ( prop ) => this.parameters !== null && this.parameters.props.has( prop ) ) ) {
			this.executeRequest();
		}
	}

	private executeRequest(): void {
		const { parameters, resolveCallbacks, rejectCallbacks } = this.getAndClearRequestData();
		Promise.resolve( this.api.get(
			{
				action: 'wbgetentities',
				ids: [ ...parameters.ids ],
				props: [ ...parameters.props ],
				...parameters.otherParams,
			},
		) ).then( ( response: unknown ) => {
			if ( !this.isWellFormedResponse( response ) ) {
				if ( this.isErrorResponse( response ) && response.error.code === 'no-such-entity' ) {
					throw new EntityNotFound( 'Entity flagged missing in response.' );
				}
				throw new TechnicalProblem( 'Result not well formed.' );
			}

			if ( [ ...parameters.ids ].some( ( entityId ) => !response.entities[ entityId ] ) ) {
				// not a real thing at the moment but guards the following access
				throw new EntityNotFound( 'Result does not contain relevant entity.' );
			}

			if ( Object.values( response.entities ).some( ( entity ) => 'missing' in entity ) ) {
				throw new EntityNotFound( 'Entity flagged missing in response.' );
			}

			resolveCallbacks.forEach( ( resolve ) => resolve( response.entities ) );
		}, ( error: JQuery.jqXHR ): never => {
			throw new JQueryTechnicalError( error );
		} ).catch( ( error ) => rejectCallbacks.forEach( ( reject ) => reject( error ) ) );
	}

	private getAndClearRequestData(): GetAndClearRequestData {
		if ( this.parameters === null ) {
			throw new Error( 'Request was dispatched, but none was scheduled.' );
		}
		const parameters = this.parameters;
		const resolveCallbacks = this.resolveCallbacks;
		const rejectCallbacks = this.rejectCallbacks;
		this.parameters = null;
		this.resolveCallbacks = [];
		this.rejectCallbacks = [];
		return { parameters, resolveCallbacks, rejectCallbacks };
	}

	private isWellFormedResponse( data: unknown ): data is WellFormedResponse {
		return typeof data === 'object' && data !== null && 'entities' in data;
	}

	private isErrorResponse( data: unknown ): data is ErrorResponse {
		return typeof data === 'object' && data !== null && 'error' in data;
	}
}
