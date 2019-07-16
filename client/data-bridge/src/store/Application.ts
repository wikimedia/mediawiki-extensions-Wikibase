import {
	NS_ENTITY,
} from './namespaces';
import EntityState from '@/store/entity/EntityState';

interface Application {
	editFlow: string;
	targetProperty: string;
}

export default Application;

export interface InitializedApplicationState extends Application {
	[ NS_ENTITY ]: EntityState;
}
