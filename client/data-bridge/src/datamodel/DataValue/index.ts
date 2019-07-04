import GlobeCoordinate from '@/datamodel/DataValue/GlobeCoordinate';
import MonolingualText from '@/datamodel/DataValue/MonolingualText';
import Quantity from '@/datamodel/DataValue/Quantity';
import Time from '@/datamodel/DataValue/Time';
import WikibaseEntityId from '@/datamodel/DataValue/WikibaseEntityId';
import DataValueType from '@/datamodel/DataValueType';

interface DataValue {
	type: DataValueType;
	value: string|GlobeCoordinate|MonolingualText|Quantity|Time|WikibaseEntityId;
}

export default DataValue;
