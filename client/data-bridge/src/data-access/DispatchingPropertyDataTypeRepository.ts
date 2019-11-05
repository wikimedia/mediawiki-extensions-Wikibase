import EntityId from '@/datamodel/EntityId';
import Datatype from '@/datamodel/DataType';
import PropertyDatatypeRepository from '@/definitions/data-access/PropertyDatatypeRepository';
import EntityInfoDispatcher, { EntityResponseWithDataType } from '@/definitions/data-access/EntityInfoDispatcher';

export default class DispatchingPropertyDataTypeRepository implements PropertyDatatypeRepository {
	private readonly requestDispatcher: EntityInfoDispatcher;

	public constructor( requestDispatcher: EntityInfoDispatcher ) {
		this.requestDispatcher = requestDispatcher;
	}

	public async getDataType( entityId: EntityId ): Promise<Datatype> {
		const entities = await this.requestDispatcher.dispatchEntitiesInfoRequest( {
			props: [ 'datatype' ],
			ids: [ entityId ],
		} );

		const entity = entities[ entityId ] as EntityResponseWithDataType;

		return entity.datatype;
	}
}
