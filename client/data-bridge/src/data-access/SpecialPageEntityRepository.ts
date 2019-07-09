import JQueryStatic from 'jquery';
import HttpStatus from 'http-status-codes';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import jqXHR = JQuery.jqXHR;
import EntityRepository from '@/definitions/data-access/EntityRepository';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';

export default class SpecialPageEntityRepository implements EntityRepository {
	private readonly $: JQueryStatic;
	private readonly specialEntityDataUrl: string;

	public constructor( $: JQueryStatic, specialEntityDataUrl: string ) {
		this.$ = $;
		this.specialEntityDataUrl = this.trimTrailingSlashes( specialEntityDataUrl );
	}

	public getEntity( entityId: string, _rev?: number ): Promise<EntityRevision> {
		return new Promise<EntityRevision>( ( resolve, reject ) => {

			return this.$.get( this.buildRequestUrl( entityId ) )
				.then( ( data ) => {
					if ( typeof data !== 'object' || !data.entities ) {
						reject( new TechnicalProblem( 'Result not well formed.' ) );
						return;
					}
					if ( !data.entities[ entityId ] ) {
						reject( new EntityNotFound( 'Result does not contain relevant entity.' ) );
						return;
					}
					resolve( new EntityRevision(
						new Entity( entityId, data.entities[ entityId ].claims ),
						data.entities[ entityId ].lastrevid,
					) );
				} )
				.catch( ( error: jqXHR ): void => {
					if ( error.status && error.status === HttpStatus.NOT_FOUND ) {
						reject( new EntityNotFound( 'Entity flagged missing in response.' ) );
					}

					reject( new JQueryTechnicalError( error ) );
				} );
		} );
	}

	private buildRequestUrl( entityId: string ): string {
		return `${this.specialEntityDataUrl}/${entityId}.json`;
	}

	private trimTrailingSlashes( string: string ): string {
		return string.replace( /\/$/, '' );
	}

}
