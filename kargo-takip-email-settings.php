<?php


function kargoTR_email_setting_page() {

    $email_template = get_option('kargoTr_email_template');

    ?>
    <div class="wrap">
        <h1>Kargo Takip Türkiye</h1>

        <form method="post" action="options.php">
            <?php settings_fields('kargoTR-settings-group'); ?>
            <?php do_settings_sections('kargoTR-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <td>
                        <label for="email_template" class="label-bold">E-Mail Şablonu</label> <br>
                        Buradan istediğiniz şekilde email şablonu oluşturabilirsiniz. <br>
                        <b>Örnek Şablon</b> : <br>
                        Merhaba {customer_name} <br>
                        {order_id} nolu siparişiniz kargoya verildi. <br>
                        Kargo şirketiniz : {company_name} <br>
                        Kargo takip numaranız : {tracking_number} <br>
                        Kargo takip linkiniz : {tracking_url} <br>
                        İyi günler dileriz.<br>
                        <br>
                        <b>Not : </b> <br>
                        <b>{customer_name} : Müşteri adı</b> <br>
                        <b>{order_id} : Sipariş numarası</b> <br>
                        <b>{company_name} : Kargo Şirket adı</b> <br>
                        <b>{tracking_number} : Kargo takip numarası</b> <br>
                        <b>{tracking_url} : Kargo takip linki</b> <br>
                    </td>
                    <td colspan="2">
                        <textarea id="email_template" style="width:100%" name="kargoTr_email_template" rows="6" ><?php echo esc_attr($email_template); ?></textarea>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

            <style>
                .label-bold {
                    text-align: center;
                    font-weight: bold;
                }
            </style>
        </form>
    </div>
<?php
}