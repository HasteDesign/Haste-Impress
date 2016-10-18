<tr class="form-field">
    <th scope="row" valign="top">
        <label for="skin_id"><?php _e( 'Presentation Skin', 'hasteimpress' ); ?></label>
    </th>
    <td>
		<select name="term_meta" id="term_meta">
			<?php
				//Check if the skin defined is default
				if( $term_meta == 'default' ) {
					echo '<option value="default" selected>' . __( 'Default', 'hasteimpress' ) . '</option>';
				} else {
					echo '<option value="default">' . __( 'Default', 'hasteimpress' ) . '</option>';
				}

				//Loop through the registered skins and compare to defined skin
				foreach($this->skins as $skin)
				{
					$name = strtolower($skin['Name']);

					if( $term_meta == $name ) {
						echo '<option selected value="'. strtolower($skin['Name']) . '" >' . $skin['Name'] . '</option>';
					} else {
						echo '<option value="'. strtolower($skin['Name']) . '" >' . $skin['Name'] . '</option>';
					}
				}
			?>
		</select>
        <!-- <input type="text" name="term_meta[skin_id]" id="term_meta[skin_id]" size="25" style="width:60%;" value="<?php /* echo $term_meta['skin_id'] ? $term_meta['skin_id'] : ''; */ ?>"> --> <br />
        <span class="description"><?php _e('Select the skin to display your presentation.'); ?></span>
    </td>
</tr>
