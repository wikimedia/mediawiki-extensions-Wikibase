import {
	convertNoSuchEntityError,
	getApiEntity,
} from '@/data-access/ApiWbgetentities';
import DataType from '@/datamodel/DataType';
import EntityId from '@/datamodel/EntityId';
import Api from '@/definitions/data-access/Api';
import { EntityWithDataType } from '@/definitions/data-access/ApiWbgetentities';
import PropertyDatatypeRepository from '@/definitions/data-access/PropertyDatatypeRepository';

export default class ApiPropertyDataTypeRepository implements PropertyDatatypeRepository {
	private readonly api: Api;

	public constructor( api: Api ) {
		this.api = api;
	}

	public async getDataType( entityId: EntityId ): Promise<DataType> {
		const response = await this.api.get( {
			action: 'wbgetentities',
			props: new Set( [ 'datatype' ] ),
			ids: new Set( [ entityId ] ),
			formatversion: 2,
		} ).catch( convertNoSuchEntityError );
		const entity = getApiEntity( response, entityId ) as EntityWithDataType;
		return entity.datatype;
	}
}
