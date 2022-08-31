<?php

//phpcs:disable VariableAnalysis
// There are "undefined" variables here because they're defined in the code that includes this file as a template.

?>
<div id="fdestadisticas-plugin-container">

	<div class="fdestadisticas-lower">

		<div class="fdestadisticas-boxes">
                  
                  <div class="wrap">

                    <h2><?php _e('Finanzas Digital - Tablas (Shortcode)') ?></h2>

                    <form class="ds8-form" method="post" action="options.php">
                    <?php settings_fields('ds8-settings-group'); ?>
                    <?php do_settings_sections('ds8-settings-page') ?>

                        <table class="form-table">
                        <?php FDEstadisticas_Admin::create_form($options); ?>
                        </table>

                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                        </p>

                    </form>
                    <?php if ( $front_page_elements !== null ) : ?>
                    <!-- Empty input field for Address -->
                    <table style="display:none;">
                    <tr id="front-page-element-placeholder" class="front-page-element" style="display:none;">
                        <th><label for="element-page-id"><?php _e('Address:') ?></label></th>
                        <td>
                            <textarea rows="6" cols="39" name="my-settings[element-page-id]"></textarea>
                            <a href="#"><?php _e('Remove') ?></a>
                        </td>
                    </tr>
                    </table>
                    <?php endif; ?>
                  </div>
                  
		</div>
	</div>
</div>