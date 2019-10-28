import EntityId from '@/datamodel/EntityId';
import Datatype from '@/datamodel/DataType';

export default interface PropertyDatatypeRepository {
	getDataType( id: EntityId ): Promise<Datatype>;
}
