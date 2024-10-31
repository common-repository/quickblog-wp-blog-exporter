<?php defined( 'ABSPATH' ) or die; ?>
<style type="text/css">
	.wrap-cpe2-google-spreadsheet label, .wrap-cpe2-google-spreadsheet input[type="text"] {display:block}
	.wrap-cpe2-google-spreadsheet .input-field{margin-bottom: 10px}
	.wrap-cpe2-google-spreadsheet .ajax-loader-cont{text-align:left;display:none}
	.wrap-cpe2-google-spreadsheet .ajax-loader-cont img{max-height: 50px}
	.wrap-cpe2-google-spreadsheet.doing-ajax .ajax-loader-cont{display:block}
	.wrap-cpe2-google-spreadsheet strong{text-decoration:underline}
	.cpe2gs-logo img{max-height:60px;width:auto;height:auto;margin-top:20px}
</style>
<div class="cpe2gs-logo">
	<a href="https://click.quickblog.co/wordpress" target="_blank">
		<img src="<?php esc_attr_e( $this->logo_url ); ?>" >
	</a>
</div>
<div class="wrap wrap-cpe2-google-spreadsheet">
<h1><?php _e( 'Quickblog WP Blog Exporter - Dashboard', 'cpe2-google-spreadsheet' ); ?></h1>
	<form method="post" action="">
		<div class="input-field">
			<label for="spreadsheet_id"><?php _e( 'Share URL', 'cpe2-google-spreadsheet' ); ?></label>
			<input type="text" name="share_url" class="regular-text">
		</div>
		<div class="input-field">
			<label for="sheet_name"><?php _e( 'Sheet Name', 'cpe2-google-spreadsheet' ); ?></label>
			<input type="text" name="sheet_name" class="regular-text">
		</div>
		<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Export', 'cpe2-google-spreadsheet' ); ?>">
	</form>
	<div class="ajax-loader-cont">
		<img src="<?php esc_attr_e( $this->ajax_loader_url ); ?>" >
	</div>
	<br>
	<?php _e( 'Note: make sure "anyone with the link has Editor access".', 'cpe2-google-spreadsheet' ); ?>
</div>