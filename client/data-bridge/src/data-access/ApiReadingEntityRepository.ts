import Entity from '@/datamodel/Entity';
import EntityRevision from '@/datamodel/EntityRevision';
import {
	convertNoSuchEntityError,
	getApiEntity,
} from '@/data-access/ApiWbgetentities';
import Api from '@/definitions/data-access/Api';
import {
	EntityWithClaims,
	EntityWithInfo,
} from '@/definitions/data-access/ApiWbgetentities';
import ReadingEntityRepository from '@/definitions/data-access/ReadingEntityRepository';

export default class ApiReadingEntityRepository implements ReadingEntityRepository {
	private readonly api: Api;

	public constructor( api: Api ) {
		this.api = api;
	}

	public async getEntity( entityId: string ): Promise<EntityRevision> {
		const response = await this.api.get( {
			action: 'wbgetentities',
			ids: new Set( [ entityId ] ),
			props: new Set( [
				'info', // for lastrevid
				'claims',
			] ),
		} ).catch( convertNoSuchEntityError );
		const entity = getApiEntity( response, entityId ) as EntityWithInfo & EntityWithClaims;

		return new EntityRevision(
			new Entity( entityId, entity.claims ),
			entity.lastrevid,
		);
	}

}
