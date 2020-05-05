import WritingEntityRepository from '@/definitions/data-access/WritingEntityRepository';
import EntityRevision from '@/datamodel/EntityRevision';
import Entity from '@/datamodel/Entity';
import StatementMap from '@/datamodel/StatementMap';
import { WritingApi } from '@/definitions/data-access/Api';

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
	private tags?: string[];

	public constructor( api: WritingApi, tags?: string[] ) {
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
		( error ) => {
			// Specialized error handling should be added here

			throw error;
		} );
	}
}
