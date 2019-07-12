import DataValue from '@/datamodel/DataValue';
import DataType from '@/datamodel/DataType';

type SnakType = 'value'|'somevalue'|'novalue';

interface Snak {
	snaktype: SnakType;
	property: string; // https://github.com/Microsoft/TypeScript/issues/6579 is accepted
	datatype: DataType;
	datavalue?: DataValue;
}

export default Snak;
