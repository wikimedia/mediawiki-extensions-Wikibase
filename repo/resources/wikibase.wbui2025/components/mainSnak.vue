<template>
	<div class="wikibase-wbui2025-main-snak">
		<div class="wikibase-wbui2025-rankselector">
			<span :class="concat( 'wikibase-rankselector-', rank )" :title="rankTitleString"></span>
		</div>
		<wbui2025-snak-value
			:snak="mainSnak"
			:statement-id="statementId"
		></wbui2025-snak-value>
	</div>
</template>

<script>
const { defineComponent } = require( 'vue' );
const wbui2025 = require( 'wikibase.wbui2025.lib' );
const Wbui2025SnakValue = require( './snakValue.vue' );

// @vue/component
module.exports = exports = defineComponent( {
	name: 'WikibaseWbui2025MainSnak',
	components: {
		Wbui2025SnakValue
	},
	props: {
		mainSnak: {
			type: Object,
			required: true
		},
		rank: {
			type: String,
			required: true
		},
		statementId: {
			type: String,
			required: true
		}
	},
	setup() {
		return {
			concat: wbui2025.util.concat
		};
	},
	computed: {
		rankTitleString() {
			// messages that can be used here:
			// * wikibase-statementview-rank-normal
			// * wikibase-statementview-rank-preferred
			// * wikibase-statementview-rank-deprecated
			return mw.msg( 'wikibase-statementview-rank-' + this.rank );
		}
	}
} );
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.wikibase-wbui2025-main-snak {
	display: flex;
	align-items: center;
	padding: @spacing-75 @spacing-50;

	.wikibase-wbui2025-rankselector {
		display: flex;
		align-items: center;
		justify-content: center;

		span {
			display: inherit;
			width: 20px;
			height: 20px;
			background-repeat: no-repeat;
			background-position: center;

			&.wikibase-rankselector-preferred {
				background-image: url( data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20xmlns%3Axlink%3D%22http%3A//www.w3.org/1999/xlink%22%20width%3D%228%22%20height%3D%2220%22%3E%3Cdefs%3E%3Cpath%20d%3D%22M3.1%2C0%200%2C3.8%200%2C6%208%2C6%208%2C3.8%204.9%2C0zm8.2%2C7%20-2.3%2C2%200%2C2%202.3%2C2%203.4%2C0%202.3%2C-2%200%2C-2%20-2.3%2C-2zm6.7%2C7%200%2C2.2%203.1%2C3.8%201.8%2C0%203.1%2C-3.8%200%2C-2.2z%22%20id%3D%22a%22/%3E%3Cpath%20d%3D%22m18.5%2C10.75%200%2C-1.5%202%2C-1.75%203%2C0%202%2C1.75%200%2C1.5%20-2%2C1.75%20-3%2C0zm0%2C-6.75%200%2C1.5%207%2C0%200%2C-1.5%20-2.875%2C-3.5%20-1.25%2C0zm-9%2C12%200%2C-1.5%207%2C0%200%2C1.5%20-2.875%2C3.5%20-1.25%2C0zm0%2C-12%200%2C1.5%207%2C0%200%2C-1.5%20-2.875%2C-3.5%20-1.25%2C0zm-9%2C12%200%2C-1.5%207%2C0%200%2C1.5%20-2.875%2C3.5%20-1.25%2C0zm0%2C-5.25%200%2C-1.5%202%2C-1.75%203%2C0%202%2C1.75%200%2C1.5%20-2%2C1.75%20-3%2C0z%22%20id%3D%22b%22%20fill%3D%22none%22/%3E%3C/defs%3E%3Cuse%20fill%3D%22%2372777d%22%20x%3D%220%22%20y%3D%220%22%20xlink%3Ahref%3D%22%23a%22/%3E%3Cuse%20stroke%3D%22%2372777d%22%20x%3D%220%22%20y%3D%220%22%20xlink%3Ahref%3D%22%23b%22/%3E%3C/svg%3E );
			}

			&.wikibase-rankselector-normal {
				background-image: url( data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20xmlns%3Axlink%3D%22http%3A//www.w3.org/1999/xlink%22%20width%3D%228%22%20height%3D%2220%22%3E%3Cdefs%3E%3Cpath%20d%3D%22M3.1%2C0%200%2C3.8%200%2C6%208%2C6%208%2C3.8%204.9%2C0zm8.2%2C7%20-2.3%2C2%200%2C2%202.3%2C2%203.4%2C0%202.3%2C-2%200%2C-2%20-2.3%2C-2zm6.7%2C7%200%2C2.2%203.1%2C3.8%201.8%2C0%203.1%2C-3.8%200%2C-2.2z%22%20id%3D%22a%22/%3E%3Cpath%20d%3D%22m18.5%2C10.75%200%2C-1.5%202%2C-1.75%203%2C0%202%2C1.75%200%2C1.5%20-2%2C1.75%20-3%2C0zm0%2C-6.75%200%2C1.5%207%2C0%200%2C-1.5%20-2.875%2C-3.5%20-1.25%2C0zm-9%2C12%200%2C-1.5%207%2C0%200%2C1.5%20-2.875%2C3.5%20-1.25%2C0zm0%2C-12%200%2C1.5%207%2C0%200%2C-1.5%20-2.875%2C-3.5%20-1.25%2C0zm-9%2C12%200%2C-1.5%207%2C0%200%2C1.5%20-2.875%2C3.5%20-1.25%2C0zm0%2C-5.25%200%2C-1.5%202%2C-1.75%203%2C0%202%2C1.75%200%2C1.5%20-2%2C1.75%20-3%2C0z%22%20id%3D%22b%22%20fill%3D%22none%22/%3E%3C/defs%3E%3Cuse%20fill%3D%22%2372777d%22%20x%3D%22-9%22%20y%3D%220%22%20xlink%3Ahref%3D%22%23a%22/%3E%3Cuse%20stroke%3D%22%2372777d%22%20x%3D%22-9%22%20y%3D%220%22%20xlink%3Ahref%3D%22%23b%22/%3E%3C/svg%3E );
			}

			&.wikibase-rankselector-deprecated {
				background-image: url( data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20xmlns%3Axlink%3D%22http%3A//www.w3.org/1999/xlink%22%20width%3D%228%22%20height%3D%2220%22%3E%3Cdefs%3E%3Cpath%20d%3D%22M3.1%2C0%200%2C3.8%200%2C6%208%2C6%208%2C3.8%204.9%2C0zm8.2%2C7%20-2.3%2C2%200%2C2%202.3%2C2%203.4%2C0%202.3%2C-2%200%2C-2%20-2.3%2C-2zm6.7%2C7%200%2C2.2%203.1%2C3.8%201.8%2C0%203.1%2C-3.8%200%2C-2.2z%22%20id%3D%22a%22/%3E%3Cpath%20d%3D%22m18.5%2C10.75%200%2C-1.5%202%2C-1.75%203%2C0%202%2C1.75%200%2C1.5%20-2%2C1.75%20-3%2C0zm0%2C-6.75%200%2C1.5%207%2C0%200%2C-1.5%20-2.875%2C-3.5%20-1.25%2C0zm-9%2C12%200%2C-1.5%207%2C0%200%2C1.5%20-2.875%2C3.5%20-1.25%2C0zm0%2C-12%200%2C1.5%207%2C0%200%2C-1.5%20-2.875%2C-3.5%20-1.25%2C0zm-9%2C12%200%2C-1.5%207%2C0%200%2C1.5%20-2.875%2C3.5%20-1.25%2C0zm0%2C-5.25%200%2C-1.5%202%2C-1.75%203%2C0%202%2C1.75%200%2C1.5%20-2%2C1.75%20-3%2C0z%22%20id%3D%22b%22%20fill%3D%22none%22/%3E%3C/defs%3E%3Cuse%20fill%3D%22%2372777d%22%20x%3D%22-18%22%20y%3D%220%22%20xlink%3Ahref%3D%22%23a%22/%3E%3Cuse%20stroke%3D%22%2372777d%22%20x%3D%22-18%22%20y%3D%220%22%20xlink%3Ahref%3D%22%23b%22/%3E%3C/svg%3E );
			}
		}
	}

	.wikibase-wbui2025-globe-coordinate-value {
		display: flex;
		justify-content: center;
		align-items: flex-start;
		gap: 1rem;
		margin: 0;
	}
}
</style>
