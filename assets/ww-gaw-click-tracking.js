(function( $ ) {
	$( document ).on( 'click', '.aw-track-click', function( event ) {
		var _this = $( this );

		if ( _this.is( 'a' ) ) {
			event.preventDefault();
			event.stopImmediatePropagation();
		}

		if ( typeof window.ww_gaw_click_tracking_defaults == 'object' && typeof window.google_trackConversion == 'function' ) {
			var _href, defaults = window.ww_gaw_click_tracking_defaults, href = _this.data( 'href' ) || _this.attr( 'href' ) || false, settings = {
				conversionCurrency : _this.data( 'conversionCurrency' ) || defaults.default_conversion_currency || '',
				conversionId       : parseInt( _this.data( 'conversionId' ) || defaults.default_conversion_id || 0 ),
				conversionLabel    : _this.data( 'conversionLabel' ) || defaults.default_conversion_label || '',
				conversionValue    : parseFloat( _this.data( 'conversionValue' ) || defaults.default_conversion_value || 0 ),
				remarketingOnly    : $.inArray( _this.data( 'remarketingOnly' ) || defaults.default_remarketing_only || false, [1, true, 'true'] ) !== -1,
			};

			if ( !href && ((_href = _this.find( '[href]' )).length || (_href = _this.find( '[data-href]' )).length) ) {
				href = _href.data( 'href' ) || _href.attr( 'href' ) || false;
			}

			if ( settings.conversionId && settings.conversionLabel ) {
				window.google_conversion_id = settings.conversionId;
				window.google_conversion_label = settings.conversionLabel;
				window.google_conversion_value = settings.conversionValue;
				window.google_conversion_currency = settings.conversionCurrency;
				window.google_remarketing_only = settings.remarketingOnly;
				window.google_conversion_format = "3";

				var trackConversion = {};

				if ( href ) {
					trackConversion.onload_callback = function() {
						window.location = href;
					};
				}

				window.google_trackConversion( trackConversion );
			}
		}
	} );
})( jQuery );