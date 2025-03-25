/**
 * External dependencies
 */
import {
	Card,
	CardBody,
	CheckboxControl,
	SelectControl,
	TextControl,
	TextareaControl,
	Button,
} from '@wordpress/components';
import { useState, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import { paymentGatewaysStore } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './settings-payments-body.scss';
import './settings-payments-form.scss';
import { PaymentSettingsLayout } from '~/settings-payments/components/payment-settings-layout';
import { PaymentSettingsSection } from '~/settings-payments/components/payment-settings-section';

/**
 * Component for managing Cash on Delivery payment gateway settings.
 */
export const SettingsPaymentsCod = () => {
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( 'core/notices' );
	const { codSettings, isLoading } = useSelect(
		( select ) => ( {
			codSettings:
				select( paymentGatewaysStore ).getPaymentGateway( 'cod' ),
			isLoading: ! select( paymentGatewaysStore ).hasFinishedResolution(
				'getPaymentGateway',
				[ 'cod' ]
			),
		} ),
		[]
	);

	const { updatePaymentGateway, invalidateResolutionForStoreSelector } =
		useDispatch( paymentGatewaysStore );

	const [ enabled, setEnabled ] = useState( false );
	const [ enableForMethods, setEnableForMethods ] = useState( [] );
	const [ enableForVirtual, setEnableForVirtual ] = useState( false );
	const [ title, setTitle ] = useState( '' );
	const [ description, setDescription ] = useState( '' );
	const [ instructions, setInstructions ] = useState( '' );

	useEffect( () => {
		if ( codSettings ) {
			setEnableForMethods(
				codSettings.settings?.enable_for_methods?.value
					? codSettings.settings.enable_for_methods.value.split( ',' )
					: []
			);
			setEnableForVirtual(
				codSettings.settings?.enable_for_virtual?.value === 'yes'
			);
			setEnabled( codSettings.enabled || false );
			setTitle(
				codSettings.settings?.title?.value || 'Cash on delivery'
			);
			setDescription( codSettings.description || '' );
			setInstructions( codSettings.settings?.instructions?.value || '' );
		}
	}, [ codSettings ] );

	const saveSettings = () => {
		updatePaymentGateway( 'cod', {
			enabled,
			description,
			settings: {
				title,
				instructions,
				enable_for_methods: enableForMethods.join( ',' ),
				enable_for_virtual: enableForVirtual ? 'yes' : 'no',
			},
		} )
			.then( () => {
				invalidateResolutionForStoreSelector( 'getPaymentGateway' );
				createSuccessNotice(
					__( 'Settings updated successfully', 'woocommerce' )
				);
			} )
			.catch( ( error ) => {
				createErrorNotice(
					__( 'Failed to update settings', 'woocommerce' )
				);
			} );
	};

	if ( isLoading ) {
		return <p>Loading settings...</p>;
	}

	return (
		<PaymentSettingsLayout>
			<PaymentSettingsSection
				id={ 'cod-settings' }
				title={ __( 'Enable and customise', 'woocommerce' ) }
				description={ __(
					'Choose how you want to present cash on delivery to your customers during checkout.',
					'woocommerce'
				) }
			>
				<Card className="payment-settings-card__wrapper cod-settings__wrapper">
					<CardBody className="cod-settings__body">
						<div className={ 'form-field' }>
							<CheckboxControl
								onChange={ ( value ) => setEnabled( value ) }
								checked={ enabled }
								// @ts-expect-error The label prop can be a string, however, the final consumer of this prop accepts ReactNode.
								label={
									<span>
										{ __(
											'Enable cash on delivery',
											'woocommerce'
										) }
									</span>
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<TextControl
								label={ __( 'Title', 'woocommerce' ) }
								help={ __(
									'This controls the title which the user sees during checkout.',
									'woocommerce'
								) }
								value={ title }
								onChange={ ( value ) => setTitle( value ) }
							/>
						</div>

						<div className={ 'form-field' }>
							<TextareaControl
								label={ __( 'Description', 'woocommerce' ) }
								help={ __(
									'Payment method description that the customer will see on your checkout.',
									'woocommerce'
								) }
								value={ description }
								onChange={ ( value ) =>
									setDescription( value )
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<TextareaControl
								label={ __( 'Instructions', 'woocommerce' ) }
								help={ __(
									'Instructions that will be added to the thank you page and emails.',
									'woocommerce'
								) }
								value={ instructions }
								onChange={ ( value ) =>
									setInstructions( value )
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<SelectControl
								className="wc-enhanced-select"
								label={ __(
									'Enable for shipping methods',
									'woocommerce'
								) }
								help={ __(
									'Select which shipping methods cash on delivery is only available for. Leave blank to enable for all methods.',
									'woocommerce'
								) }
								value={ enableForMethods }
								multiple
								style={ { width: '400px' } }
								options={ Object.entries(
									codSettings.settings?.enable_for_methods
										?.options || {}
								).flatMap( ( [ groupLabel, groupOptions ] ) =>
									Object.entries( groupOptions ).map(
										( [ value, label ] ) => ( {
											label: label.replace(
												/&quot;/g,
												'"'
											),
											value,
										} )
									)
								) }
								onChange={ ( selected ) =>
									setEnableForMethods( selected )
								}
							/>
						</div>

						<div className={ 'form-field' }>
							<CheckboxControl
								onChange={ ( value ) =>
									setEnableForVirtual( value )
								}
								checked={ enableForVirtual }
								help={ __(
									'Accept cash on delivery if the order is virtual',
									'woocommerce'
								) }
								// @ts-expect-error The label prop can be a string, however, the final consumer of this prop accepts ReactNode.
								label={
									<span>
										{ __(
											'Accept for virtual orders',
											'woocommerce'
										) }
									</span>
								}
							/>
						</div>
					</CardBody>
				</Card>
			</PaymentSettingsSection>
			<Card className={ 'payment-settings-card__wrapper ' }>
				<CardBody className={ 'form__actions' }>
					<Button variant={ 'primary' } onClick={ saveSettings }>
						{ __( 'Save changes', 'woocommerce' ) }
					</Button>
				</CardBody>
			</Card>
		</PaymentSettingsLayout>
	);
};

export default SettingsPaymentsCod;
