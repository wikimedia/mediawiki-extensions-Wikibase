import { StatementMap } from '@wmde/wikibase-datamodel-types';
import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';
import { WritingApi } from '@/definitions/data-access/Api';
import ApiErrors from '@/data-access/error/ApiErrors';
import ApplicationError, { ErrorTypes } from '@/definitions/ApplicationError';
import SavingError from '@/data-access/error/SavingError';

interface ApiResponseEntity {
	id: string;
	claims: StatementMap;
	lastrevid: number;
}

interface ResponseSuccess {
	success: number;
	entity: ApiResponseEntity;
}

export default class ApiWritingRepository implements WritingEntityRepository {
	private api: WritingApi;
	private tags?: readonly string[];

	public constructor( api: WritingApi, tags?: readonly string[] ) {
		this.api = api;
		this.tags = tags || undefined;
	}

	public saveEntity( entity: Entity, base?: EntityRevision, assertUser = true ): Promise<EntityRevision> {
		const params = {
			action: 'wbeditentity',
			id: entity.id,
			baserevid: base?.revisionId,
			data: JSON.stringify( {
				claims: entity.statements,
			} ),
			tags: this.tags,
		};
		let promise;

		if ( assertUser ) {
			promise = this.api.postWithEditTokenAndAssertUser( params );
		} else {
			promise = this.api.postWithEditToken( params );
		}
		return promise.then( ( response: unknown ): EntityRevision => {
			return new EntityRevision(
				new Entity(
					( response as ResponseSuccess ).entity.id,
					( response as ResponseSuccess ).entity.claims,
				),
				( response as ResponseSuccess ).entity.lastrevid,
			);
		},
		( error: Error ): never => {
			if ( !( error instanceof ApiErrors ) ) {
				throw error;
			}

			throw new SavingError( error.errors.map( ( apiError ): ApplicationError => {
				switch ( apiError.code ) {
					case 'assertanonfailed':
						return { type: ErrorTypes.ASSERT_ANON_FAILED, info: apiError };
					case 'assertuserfailed':
						return { type: ErrorTypes.ASSERT_USER_FAILED, info: apiError };
					case 'assertnameduserfailed':
						return { type: ErrorTypes.ASSERT_NAMED_USER_FAILED, info: apiError };
					case 'editconflict':
						return { type: ErrorTypes.EDIT_CONFLICT, info: apiError };
					case 'nosuchrevid':
						return { type: ErrorTypes.NO_SUCH_REVID, info: apiError };
					case 'badtags':
						return { type: ErrorTypes.BAD_TAGS, info: apiError };
					default:
						return { type: ErrorTypes.SAVING_FAILED, info: apiError };
				}
			} ) );
		} );
	}
}
