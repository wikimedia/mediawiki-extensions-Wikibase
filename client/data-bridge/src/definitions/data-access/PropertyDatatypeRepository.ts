import { DataType } from '@wmde/wikibase-datamodel-types';
import EntityId from '@/datamodel/EntityId';

export default interface PropertyDatatypeRepository {
	getDataType( id: EntityId ): Promise<DataType>;
}
