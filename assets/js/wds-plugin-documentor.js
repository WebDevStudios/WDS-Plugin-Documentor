/**
 * WDS Plugin Documentor - v0.1.0 - 2024-01-15
 * http://webdevstudios.com/
 *
 * Copyright (c) 2024;
 * Licensed GPLv2+
 */
/*jslint browser: true */
/*global jQuery:false */

window.WDS_Plugin_Documentor = window.WDS_Plugin_Documentor || {};
( function( window, document, $, app, undefined ) {
	'use strict';

	app.cache = function() {
		app.$plugin_wrapper  = $( '.wp-list-table.plugins' );
		app.$sliders         = app.$plugin_wrapper.find('.slider' );
		app.$info_blocks     = app.$plugin_wrapper.find( 'tr.wds-plugin-doc.info' );

		app.$edit_post_body  = $( 'body.post-type-wds-plugin-doc' );
		app.$edit_post_title = app.$edit_post_body.find( 'input[name="post_title"]' );
		app.$btn_add_new     = app.$edit_post_body.find( 'a.add-new-h2, .page-title-action' );
	};

	app.init = function() {
		app.cache();

		if ( app.$plugin_wrapper ) {
			$( 'body').on( 'click', 'a.wds-plugin-doc.has_info', app.show_info );
		}

		var $cols = app.$plugin_wrapper.find( 'thead > tr > *' ).length;

		app.$info_blocks.each( function() {
			var $this = $( this );
			$this.find( 'td' ).attr( 'colspan', $cols );
		} );

		// Disable the post title, we want the plugin to determine the title.
		if ( app.$edit_post_body.length ) {
			app.$edit_post_title.prop( 'readonly', true );

			if ( app.$btn_add_new.length ) {

				// Another hack since WP doesn't have a hook for this.
				app.$btn_add_new.text( 'Back to Plugins' );
				app.$btn_add_new.each( function() {
					this.href = app.plugins_url;
				});
			} else {
				$('.wp-heading-inline').after( '<a href="' + app.plugins_url + '" class="page-title-action">Back to Plugins</a>' );
			}
		}

		$('.wds-plugin-doc.submitdelete').on( 'click', function( evt ) {
			if ( ! window.confirm( "You are about to delete this plugin's notes. 'Cancel' to stop, 'OK' to delete." ) ) {
				evt.preventDefault();
			}
		});
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

} )( window, document, jQuery, window.WDS_Plugin_Documentor );
