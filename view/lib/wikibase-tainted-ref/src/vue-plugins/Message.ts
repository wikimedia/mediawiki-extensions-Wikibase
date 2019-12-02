import Vue, { VueConstructor } from 'vue';
import MessageOptions from '@/@types/MessageOptions';

export default function Message(
	vueConstructor: VueConstructor<Vue>,
	options: MessageOptions,
): void {
	vueConstructor.prototype.$message = ( key: string ): string => {
		return options.messageToTextFunction( key );
	};
}
