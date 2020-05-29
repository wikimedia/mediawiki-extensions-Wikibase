import { StatementMap } from '@wmde/wikibase-datamodel-types';
import JQueryStatic from 'jquery';
import HttpStatus from 'http-status-codes';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import ReadingEntityRevisionRepository from '@/definitions/data-access/ReadingEntityRevisionRepository';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';
import jqXHR = JQuery.jqXHR;
import EntityId from '@/datamodel/EntityId';

export interface SpecialPageWikibaseEntityResponse {
	entities: {
		[x: string]: {
			id: EntityId;
			claims: StatementMap;
			lastrevid: number;
		};
	};
}

// TODO implement ReadingEntityRepository as well (T251245)
export default class SpecialPageReadingEntityRepository
implements /* ReadingEntityRepository, */ ReadingEntityRevisionRepository {
	private readonly $: JQueryStatic;
	private readonly specialEntityDataUrl: string;

	public constructor( $: JQueryStatic, specialEntityDataUrl: string ) {
		this.$ = $;
		this.specialEntityDataUrl = this.trimTrailingSlashes( specialEntityDataUrl );
	}

	public getEntity( entityId: string, rev: number ): Promise<EntityRevision> {
		return Promise.resolve( this.$.get( ...this.buildRequestParams( entityId, rev ) ) )
			.then( ( data: unknown ): EntityRevision => {
				if ( !this.isWellFormedResponse( data ) ) {
					throw new TechnicalProblem( 'Result not well formed.' );
				}
				if ( !data.entities[ entityId ] ) {
					throw new EntityNotFound( 'Result does not contain relevant entity.' );
				}
				return new EntityRevision(
					new Entity( entityId, data.entities[ entityId ].claims ),
					data.entities[ entityId ].lastrevid,
				);
			}, ( error: jqXHR ): never => {
				if ( error.status && error.status === HttpStatus.NOT_FOUND ) {
					throw new EntityNotFound( 'Entity flagged missing in response.' );
				}

				throw new JQueryTechnicalError( error );
			} );

	}

	private isWellFormedResponse( data: unknown ): data is SpecialPageWikibaseEntityResponse {
		return typeof data === 'object' && data !== null && 'entities' in data;
	}

	private buildRequestParams( entityId: string, revisionId: number|undefined ): [ string, object ] {
		const url = `${this.specialEntityDataUrl}/${entityId}.json`;
		if ( revisionId !== undefined ) {
			return [ url, { revision: revisionId } ];
		} else {
			return [ url, {} ];
		}
	}

	private trimTrailingSlashes( string: string ): string {
		return string.replace( /\/$/, '' );
	}

}
