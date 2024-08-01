<div class="wrap">
    <h1>Bulk Price Editor</h1>
    <form id="fbpe-update-form" method="post">
        <?php
            settings_fields('fbpe_settings_group');
            ?>
        <div class="fbpe-main-form">
            <?php
            do_settings_sections('bulk-price-editor');
            ?>
        </div>
        <div class="fbpe-extra-field">
            <table>
                <tbody>
                    
                </tbody>
            </table>
        </div>
        <div id="fbpe-progress-container">
            <div id="fbpe-progress-bar" >0%</div>
            <div id="fbpe-progress-text">0% completed</div>
        </div>
        <ul id="fbpe-updated-products"></ul>


        <?php
        submit_button('Update Prices');
        ?>
        <input type="hidden" name="action" value="fbpe_update_prices">
        <?php wp_nonce_field('fbpe_nonce', 'nonce'); ?>
    </form>
</div>
