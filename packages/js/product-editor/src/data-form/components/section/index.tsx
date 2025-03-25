/**
 * External dependencies
 */
import { createElement, useMemo } from '@wordpress/element';
import { Template } from '@wordpress/blocks';
import { store as coreDataStore } from '@wordpress/core-data';
import { useSelect, useDispatch } from '@wordpress/data';
import classNames from 'classnames';
import { Product } from '@woocommerce/data';
import { DataForm } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { SectionHeader } from '../../../components/section-header';

type ProductSectionProps = {
	sectionTemplate: Template;
	postType: string;
	productId: number;
};

/**
 * @todo: This is a temporary solution to get the fields for the DataForm.
 * We need to move this into a hook with a useMemo or something as we did have to generate some of this on the fly.
 * For example, label comes from the sectionTemplate. Also things like the id which matches the product key comes from the config as well.
 * You can see an example here: https://github.com/woocommerce/woocommerce/blob/89068601d334953e2904ecf56f528fc271c7b9ec/plugins/woocommerce/src/Internal/Features/ProductBlockEditor/ProductTemplates/SimpleProductTemplate.php#L192
 */
const fields = [
	{
		id: 'name',
		type: 'text',
		label: 'Title',
	},
];

export function ProductSection( {
	sectionTemplate,
	productId,
	postType,
}: ProductSectionProps ) {
	const { description, title, blockGap } = sectionTemplate[ 1 ] as {
		description: string;
		title: string;
		blockGap: string;
	};

	const { editEntityRecord } = useDispatch( coreDataStore );
	const { record, hasFinishedResolution } = useSelect(
		( select ) => {
			const {
				getEditedEntityRecord,
				hasFinishedResolution: hasFinished,
			} = select( coreDataStore );

			const args = [ 'postType', postType, productId ];
			return {
				record: getEditedEntityRecord( ...args ) as Product,
				hasFinishedResolution: hasFinished(
					'getEditedEntityRecord',
					args
				),
			};
		},
		[ postType, productId ]
	);

	const nestedClassNames = classNames(
		'wp-block-woocommerce-product-section-header__content',
		`wp-block-woocommerce-product-section-header__content--block-gap-${ blockGap }`
	);
	const SectionTagName = title ? 'fieldset' : 'div';

	const form = useMemo( () => {
		return {
			type: 'regular' as const,
			fields: sectionTemplate[ 2 ]
				.filter(
					( field ) => field[ 0 ] === 'woocommerce/product-name-field'
				)
				.map( () => 'name' ),
		};
	}, [ sectionTemplate ] );

	const onChange = ( edits: Partial< Product > ) => {
		editEntityRecord( 'postType', postType, productId, edits );
	};

	return (
		<SectionTagName>
			{ title && (
				<SectionHeader
					description={ description }
					sectionTagName={ SectionTagName }
					title={ title }
				/>
			) }

			<div className={ nestedClassNames }>
				{ hasFinishedResolution && (
					<DataForm
						// @ts-expect-error fields is not typed
						fields={ fields }
						form={ form }
						onChange={ onChange }
						data={ record }
					/>
				) }
				<p>
					Render DataForm with { sectionTemplate[ 2 ]?.length } fields
				</p>
				<ul>
					{ sectionTemplate[ 2 ]?.map( ( field ) => (
						<li key={ field[ 0 ] }>{ field[ 0 ] }</li>
					) ) }
				</ul>
			</div>
		</SectionTagName>
	);
}
