import IconMessageBox from '@/presentation/components/IconMessageBox.vue';

export default {
	title: 'IconMessageBox',
	component: IconMessageBox,
};

export function error() {
	return {
		components: { IconMessageBox },
		template:
			`
				<div>
				<IconMessageBox type="error">
					Something went wrong!
				</IconMessageBox>
				</div>`,
	};
}

export function errorLong() {
	return {
		components: { IconMessageBox },
		template:
			`
				<div>
				<IconMessageBox type="error">
					{{ new Array( 42 ).fill( 'Something went wrong!' ).join( ' ' ) }}
				</IconMessageBox>
				</div>`,
	};
}

export function errorInline() {
	return {
		components: { IconMessageBox },
		template:
			`
				<div>
				<IconMessageBox type="error" :inline="true">
					Something went wrong!
				</IconMessageBox>
				</div>`,
	};
}

export function warning() {
	return {
		components: { IconMessageBox },
		template:
			`
				<div>
				<IconMessageBox type="warning">
					I think you ought to know...
				</IconMessageBox>
				</div>`,
	};
}

export function warningLong() {
	return {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="warning">
					{{ new Array( 42 ).fill( 'I think you ought to know...' ).join( ' ' ) }}
				</IconMessageBox>
			</div>`,
	};
}

export function warningInline() {
	return {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="warning" :inline="true">
					I think you ought to know...
				</IconMessageBox>
			</div>`,
	};
}

export function notice() {
	return {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="notice">
					Just to inform you...
				</IconMessageBox>
			</div>`,
	};
}

export function noticeLong() {
	return {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="notice">
					{{ new Array( 42 ).fill( 'Just to inform you…' ).join( ' ' ) }}
				</IconMessageBox>
			</div>`,
	};
}

export function noticeInline() {
	return {
		components: { IconMessageBox },
		template:
			`<div>
				<IconMessageBox type="notice" :inline="true">
					Just to inform you...
				</IconMessageBox>
			</div>`,
	};
}
