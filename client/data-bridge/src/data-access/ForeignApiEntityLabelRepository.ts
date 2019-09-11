import { ForeignApi } from '@/@types/mediawiki/MwWindow';
import EntityLabelRepository from '@/definitions/data-access/EntityLabelRepository';
import Term from '@/datamodel/Term';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import EntityWithoutLabelInLanguageException from '@/data-access/error/EntityWithoutLabelInLanguageException';

interface WellFormedResponse {
	entities: {
		[ entityId: string ]: {
			labels: {
				[ lang: string ]: {
					language: string;
					value: string;
					'for-language'?: string;
				};
			};
		};
	};
}

interface ErrorResponse {
	error: {
		code: string;
	};
}

export default class ForeignApiEntityLabelRepository implements EntityLabelRepository {
	private foreignApi: ForeignApi;
	private readonly forLanguageCode: string;

	public constructor( forLanguageCode: string, api: ForeignApi ) {
		this.forLanguageCode = forLanguageCode;
		this.foreignApi = api;
	}

	public getLabel( entityId: string ): Promise<Term> {
		return Promise.resolve( this.foreignApi.get(
			{
				action: 'wbgetentities',
				props: 'labels',
				languages: this.forLanguageCode,
				languagefallback: 1,
				ids: entityId,
			},
		) ).then( ( response: unknown ) => {
			if ( !this.isWellFormedResponse( response ) ) {
				if ( this.isErrorResponse( response ) && response.error.code === 'no-such-entity' ) {
					throw new EntityNotFound( 'Entity flagged missing in response.' );
				}
				throw new TechnicalProblem( 'Result not well formed.' );
			}
			if ( !response.entities[ entityId ] ) { // not a real thing at the moment but guards the following access
				throw new EntityNotFound( 'Result does not contain relevant entity.' );
			}
			const entity = response.entities[ entityId ];
			if ( 'missing' in entity ) {
				throw new EntityNotFound( 'Entity flagged missing in response.' );
			}
			if ( !( this.forLanguageCode in entity.labels ) ) {
				throw new EntityWithoutLabelInLanguageException(
					`Could not find label for language '${this.forLanguageCode}'.`,
				);
			}
			const label = entity.labels[ this.forLanguageCode ];
			return {
				value: label.value,
				language: label.language,
			};
		}, ( error: JQuery.jqXHR ): never => {
			throw new JQueryTechnicalError( error );
		} );
	}

	private isWellFormedResponse( data: unknown ): data is WellFormedResponse {
		return typeof data === 'object' && data !== null && 'entities' in data;
	}

	private isErrorResponse( data: unknown ): data is ErrorResponse {
		return typeof data === 'object' && data !== null && 'error' in data;
	}
}
