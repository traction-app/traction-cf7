<?php 
    $post = get_query_var('post');
    $mail_tags = get_mail_tags( $post );   

    $wpcf7_api_data = $post->prop( 'wpcf7_api_data' );    
    $wpcf7_api_data["endpoint"] = isset( $wpcf7_api_data["endpoint"] ) ? $wpcf7_api_data["endpoint"] : '';
    $wpcf7_api_data["has_mapped_fields"] = isset( $wpcf7_api_data["has_mapped_fields"] ) ? $wpcf7_api_data["has_mapped_fields"] : '';
    $wpcf7_api_data["map_fields"] = isset( $wpcf7_api_data["map_fields"] ) ? $wpcf7_api_data["map_fields"] : array();

?>

<h2>Traction</h2>

<?php do_action( 'before_base_fields' , $post ); ?>

<fieldset>
    <div class="cf7_row">

        <div class="cf7_row">
            <label for="wpcf7-sf-endpoint">
                <p><?php _e( 'Insira o endpoint de seu formulário no Traction.' );?></p>
                <p>Para copiar seu endpoint, acesse sua página no Traction Leads e acesse a página "Formulários".</p>
                <input type="text" id="wpcf7-sf-endpoint" placeholder="https://" name="wpcf7-sf[endpoint]" class="large-text" value="<?php echo $wpcf7_api_data["endpoint"];?>" />
            </label>
        </div>

        <br>

        <label for="wpcf7-sf-has_mapped_fields">
            <input type="checkbox" id="wpcf7-sf-has_mapped_fields" name="wpcf7-sf[has_mapped_fields]" <?php checked( $wpcf7_api_data["has_mapped_fields"] , "on" );?>/>
            <?php _e( 'Mapear campos? Se não for mapeado, os campos serão enviados utilizando os mesmos nomes do Contact Form 7.' );?>
        </label>

      
        <div class="mapper-box"<?php echo $wpcf7_api_data["has_mapped_fields"] == 'on' ? ' style="display:block"' : '' ?>>
           
        <table class="form-table">
            <tbody>
                <?php 
                    foreach($mail_tags as $tag):
                        $field = $tag['name'];
                        $value = isset($wpcf7_api_data['map_fields']) ? $wpcf7_api_data['map_fields'][$field] : '';
                ?>
                <tr>
                    <th scope="row">
                        <label for="id_<?php echo $field ?>"><?php echo $field ?></label>
                    </th>
                    <td>
                        <input type="text" id="id_<?php echo $field ?>" name="wpcf7-sf[map_fields][<?php echo $field ?>]" value="<?php echo $value; ?>" class="large-text">
                    </td>
                </tr>
                <?php 
                    endforeach;
                ?>
            </tbody>
        </table>

        </div>
   
        

    </div>

</fieldset>

<?php do_action( 'after_base_fields' , $post ); ?>


<style>
.mapper-box {     
    display: none;
    padding: 20px;
    background-color: #fff;
    margin-top: 10px;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
}
</style>

<script>
jQuery(document).ready(function($){
    $('#wpcf7-sf-has_mapped_fields').on('change', function() {
        if (this.checked) {
            $('.mapper-box').show();
        } else {
            $('.mapper-box').hide();
        }           
    })
});
</script>