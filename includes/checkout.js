const kp_settings = window.wc.wcSettings.getSetting( 'kueski-gateway_data', {} );
const kp_label = window.wp.htmlEntities.decodeEntities( kp_settings.title ) || window.wp.i18n.__( 'Kueski Gateway', 'kueskipay-gateway' );
const kp_image_url = kp_settings.plugin_url+'images/kueski.png';

const KPLabel = () => {
    
    return wp.element.createElement(
        'div',
        {},
        wp.element.createElement('span', {}, kp_label),
        wp.element.createElement('img', {
            src: kp_image_url, 
            alt:'ok',
            style: {marginLeft: '5px', verticalAlign: 'top'}
        }),
    );
}

const KPContent = () => {
    return wp.element.createElement('div', {
        dangerouslySetInnerHTML: { __html: kp_settings.description || ''}
    });
};
const Kueski_Gateway = {
    name: 'kueski-gateway',
    label: Object( wp.element.createElement )(KPLabel, null),
    content: Object( window.wp.element.createElement )( KPContent, null ),
    edit: Object( window.wp.element.createElement )( KPContent, null ),
    canMakePayment: () => true,
    ariaLabel: kp_label,
    supports: {
        features: kp_settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Kueski_Gateway );