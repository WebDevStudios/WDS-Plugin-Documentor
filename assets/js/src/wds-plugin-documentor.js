/**
 * WDS Plugin Documentor
 * http://webdevstudios.com/
 *
 * Copyright (c) 2015 WebDevStudios, Jay Wood
 * Licensed under the GPLv2+ license.
 */

/*jslint browser: true */
/*global jQuery:false */

window.WDS_Plugin_Documentor = (function(window, document, $, undefined) {
	'use strict';

	var app = {};

	app.cache = function() {
		app.$plugin_wrapper  = $( '.wp-list-table.plugins' );
		app.$sliders         = app.$plugin_wrapper.find('.slider' );
		app.$info_blocks     = app.$plugin_wrapper.find( 'tr.wds-plugin-doc.info' );

		app.$edit_post_body  = $( 'body.post-type-wds-plugin-doc' );
		app.$edit_post_title = app.$edit_post_body.find( 'input[name="post_title"]' );
		app.$btn_add_new     = app.$edit_post_body.find( 'a.add-new-h2' );
	};

	app.init = function() {
		app.cache();

		if ( app.$plugin_wrapper ) {
			$( 'body').on( 'click', 'a.wds-plugin-doc.has_info', app.show_info );
		}

		// Disable the post title, we want the plugin to determine the title.
		if ( app.$edit_post_body.length ) {
			app.$edit_post_title.prop( 'readonly', true );
			app.$btn_add_new.remove(); // Another hack since WP doesn't have a hook for this.
		}

	};

	app.show_info = function( evt ) {
		evt.preventDefault();
		var $this = $( this );
		var $info_row = app.$plugin_wrapper.find( 'tr.wds-plugin-doc.info.' + $this.data( 'post' ) );

		if ( $this.hasClass( 'opened' ) ) {

			$this.removeClass( 'opened' );
			$info_row.fadeOut( 200 );

		} else {

			$this.addClass( 'opened' );

			app.$info_blocks.fadeOut( 100, function() {
				$info_row.fadeIn( 200 );
			});
		}

	};

	$(document).ready( app.init );

	return app;

})(window, document, jQuery);
