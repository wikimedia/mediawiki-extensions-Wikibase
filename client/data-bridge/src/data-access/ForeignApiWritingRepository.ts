import { ForeignApi } from '@/@types/mediawiki/MwWindow';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';
import TechnicalProblem from '@/data-access/error/TechnicalProblem';
import JQueryTechnicalError from '@/data-access/error/JQueryTechnicalError';
import EntityNotFound from '@/data-access/error/EntityNotFound';
import HttpStatus from 'http-status-codes';
import StatementMap from '@/datamodel/StatementMap';

interface ApiResponseEntity {
	id: string;
	claims: StatementMap;
	lastrevid: number;
}

interface ResponseSuccess {
	success: number;
	entity: ApiResponseEntity;
}

interface ResponseError {
	error: { code: string };
}

type Response = ResponseError|ResponseSuccess;

export default class ForeignApiWritingRepository implements WritingEntityRepository {
	private foreignApi: ForeignApi;
	private username?: string;
	private tags?: string[];

	public constructor( api: ForeignApi, username: string|null, tags?: string[] ) {
		this.foreignApi = api;
		this.username = username || undefined;
		this.tags = tags || undefined;
	}

	private static isError( response: Response ): response is ResponseError {
		return !!( ( response as ResponseError ).error );
	}

	public saveEntity( revision: EntityRevision ): Promise<EntityRevision> {
		return Promise.resolve(
			this.foreignApi.postWithEditToken( {
				action: 'wbeditentity',
				id: revision.entity.id,
				baserevid: revision.revisionId,
				data: JSON.stringify( {
					claims: revision.entity.statements,
				} ),
				assertuser: this.username,
				tags: this.tags,
			} ),
		).then( ( response: Response ): EntityRevision => {
			if ( typeof response !== 'object' ) {
				throw new TechnicalProblem( 'unknown response type.' );
			}
			if ( ForeignApiWritingRepository.isError( response ) ) {
				throw new TechnicalProblem( response.error.code );
			}

			return new EntityRevision(
				new Entity(
					response.entity.id,
					response.entity.claims,
				),
				response.entity.lastrevid,
			);

		}, ( error: JQuery.jqXHR ): never => {
			if ( error.status && error.status === HttpStatus.NOT_FOUND ) {
				throw new EntityNotFound( 'The given api page does not exist.' );
			}
			throw new JQueryTechnicalError( error );
		} );
	}
}
