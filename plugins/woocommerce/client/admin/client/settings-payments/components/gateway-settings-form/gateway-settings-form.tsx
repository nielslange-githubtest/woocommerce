/**
 * External dependencies
 */
import {
	TextControl,
	TextareaControl,
	SelectControl,
	CheckboxControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { PaymentSettingsSection } from '~/settings-payments/components/payment-settings-section';

export type GatewayFieldConfig =
	| {
			id: string;
			label: string;
			type:
				| 'text'
				| 'email'
				| 'number'
				| 'password'
				| 'textarea'
				| 'select'
				| 'radio';
			description?: string;
			placeholder?: string;
			options?: Record< string, string >;
			value: string;
			customRender?: (
				value: string,
				onChange: ( val: string ) => void
			) => React.ReactNode;
	  }
	| {
			id: string;
			label: string;
			type: 'checkbox';
			description?: string;
			value: boolean;
			customRender?: (
				value: boolean,
				onChange: ( val: boolean ) => void
			) => React.ReactNode;
	  }
	| {
			id: string;
			label: string;
			type: 'multiselect';
			description?: string;
			options: Record< string, string >;
			value: string[];
			customRender?: (
				value: string[],
				onChange: ( val: string[] ) => void
			) => React.ReactNode;
	  };

export type GatewaySectionConfig = {
	id: string;
	title: string;
	description: string;
	fields: GatewayFieldConfig[];
};

interface GatewaySettingsFormProps {
	onChange: (
		updatedSettings: Record< string, string | boolean | string[] >
	) => void;
	sections: GatewaySectionConfig[];
}

export const GatewaySettingsForm: React.FC< GatewaySettingsFormProps > = ( {
	onChange,
	sections,
} ) => {
	const updateField = ( id: string, value: string | boolean | string[] ) => {
		const updatedValues = sections.reduce( ( acc, section ) => {
			section.fields.forEach( ( field ) => {
				acc[ field.id ] = field.id === id ? value : field.value;
			} );
			return acc;
		}, {} as Record< string, string | boolean | string[] > );

		onChange( updatedValues );
	};

	const renderField = ( field: GatewayFieldConfig ) => {
		switch ( field.type ) {
			case 'text':
			case 'email':
			case 'number':
			case 'password': {
				const value = field.value as string;
				return (
					<TextControl
						key={ field.id }
						label={ field.label }
						value={ value }
						help={ field.description }
						placeholder={ field.placeholder }
						onChange={ ( val ) => updateField( field.id, val ) }
					/>
				);
			}
			case 'textarea': {
				const value = field.value as string;
				return (
					<TextareaControl
						key={ field.id }
						label={ field.label }
						value={ value }
						help={ field.description }
						placeholder={ field.placeholder }
						onChange={ ( val ) => updateField( field.id, val ) }
					/>
				);
			}
			case 'select':
			case 'radio': {
				const value = field.value as string;
				return (
					<SelectControl
						key={ field.id }
						label={ field.label }
						value={ value }
						options={ Object.entries( field.options || {} ).map(
							( [ val, label ] ) => ( {
								label,
								value: val,
							} )
						) }
						onChange={ ( val ) => updateField( field.id, val ) }
					/>
				);
			}
			case 'checkbox': {
				const value = field.value as boolean;
				return (
					<CheckboxControl
						key={ field.id }
						label={ field.label }
						checked={ value }
						help={ field.description }
						onChange={ ( checked ) =>
							updateField( field.id, checked )
						}
					/>
				);
			}
			case 'multiselect': {
				const value = field.value as string[];
				return (
					<SelectControl
						key={ field.id }
						label={ field.label }
						multiple
						value={ value }
						options={ Object.entries( field.options ).map(
							( [ val, label ] ) => ( {
								label,
								value: val,
							} )
						) }
						onChange={ ( val ) => updateField( field.id, val ) }
					/>
				);
			}
			default:
				return null;
		}
	};

	return (
		<>
			{ sections.map( ( section ) => (
				<PaymentSettingsSection
					key={ section.id }
					title={ section.title }
					description={ section.description }
				>
					{ section.fields.map( renderField ) }
				</PaymentSettingsSection>
			) ) }
		</>
	);
};
