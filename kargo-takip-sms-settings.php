<?php

include('kobikom-helper.php');

function kargoTR_sms_setting_page(){

    $sms_provider = get_option('sms_provider');

    $NetGsm_UserName = get_option('NetGsm_UserName');
    $NetGsm_Password = get_option('NetGsm_Password');
    $NetGsm_Header = get_option('NetGsm_Header');
    $NetGsm_sms_url_send = get_option('NetGsm_sms_url_send');

    //kobikom
    $Kobikom_ApiKey = get_option('Kobikom_ApiKey');
    $KobiKom_Header = get_option('Kobikom_Header');

    ?>
    <div class="wrap">
        <h1>Kargo Takip Türkiye</h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'kargoTR-settings-group' ); ?>
            <?php do_settings_sections( 'kargoTR-settings-group' ); ?>
            <table class="form-table">
 
 
                <tr valign="top">
                    <th scope="row" style="width:50%">
                        <?php _e( 'Otomatik SMS Gönderilsin mi ? Gönderilmesini istiyorsanız firma seçin', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <input type="radio" id="yokSms" <?php if( $sms_provider == 'no' ) echo 'checked'?>
                            name="sms_provider" value="no">
                        <label for="yokSms">Yok</label><br>
                    </td>
                    <td>
                        <input type="radio" id="NetGSM" <?php if( $sms_provider == 'NetGSM' ) echo 'checked'?>
                            name="sms_provider" value="NetGSM">
                        <label for="NetGSM">NetGSM</label><br>
                    </td>

                    <td>
                        <input type="radio" id="NetGSM" <?php if( $sms_provider == 'Kobikom' ) echo 'checked'?>
                            name="sms_provider" value="Kobikom">
                        <label for="Kobikom">Kobikom</label><br>
                    </td>
                </tr>

                <tr class="netgsm" <?php if( $sms_provider != 'NetGSM' ) echo 'style="display:none"'?>>
                    <th scope="row" style="width:50%">
                        <hr>
                    </th>
                    <td>
                        <hr>
                    </td>
                    <td>
                        <hr>
                    </td>
                </tr>

                <tr valign="top" class="netgsm" <?php if( $sms_provider != 'NetGSM' ) echo 'style="display:none"'?>>
                    <th scope="row" style="width:25%">
                        <?php _e( 'NetGSM Bilgileriniz <br> Abone numarasının başında 0 olmadan giriniz orneğin 212xxxxxx <br> Şifrenizide girdikten sonra kaydedin eğer şifre ve abone numaranız dogruysa <br> Sms baslıklarınız çıkacaktır <br> Lütfen başlık seçip kaydedin tekrardan', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <label for="NetGsm_UserName" class="label-bold">Abone Numarası </label> <br>
                        <input type="text" id="NetGsm_UserName" name="NetGsm_UserName"
                            value="<?php echo esc_attr($NetGsm_UserName); ?>">
                    </td>
                    <td>
                        <label for="NetGSM" class="label-bold">NetGSM Şifresi</label> <br>
                        <input type="password" id="NetGSM" name="NetGsm_Password"
                            value="<?php echo __($NetGsm_Password);?>">
                        <br>
                    </td>
                </tr>

                <tr valign="top" class="netgsm" <?php if ($sms_provider != 'NetGSM') echo 'style="display:none"'?>>
                    <th scope="row" style="width:25%"></th>
                    <td>
                        <label for="NetGsm_Header" class="label-bold">SMS Başlığınız </label> <br>
                        <?php
                                if ($NetGsm_Password && $NetGsm_UserName) {
                                    $netGsm_Header_get = kargoTR_get_netgsm_headers($NetGsm_UserName,$NetGsm_Password);
                                    if (!$netGsm_Header_get) {
                                        echo 'NetGSM kullanici adi veya sifreniz yanlis';
                                    } else {
                                        echo '<select name="NetGsm_Header" id="NetGsm_Header">';
                                        foreach ($netGsm_Header_get as $key => $value) {
                                            if ($NetGsm_Header == $value) {
                                                echo '<option selected value="'.$value.'">'.$value.'</option>';
                                            } else {
                                                echo '<option value="'.$value.'">'.$value.'</option>';
                                            }
                                        }
                                        echo '</select>';
                                    }
                                }
                            ?>
                    </td>
                    <td>
                        <?php
                                if ($NetGsm_Password && $NetGsm_UserName) {
                                    $NetGSM_packet_info = kargoTR_get_netgsm_packet_info($NetGsm_UserName,$NetGsm_Password);
                                    $NetGSM_credit_info = kargoTR_get_netgsm_credit_info($NetGsm_UserName,$NetGsm_Password);
                                    if ($NetGSM_packet_info) {
                                        echo '<b>Kalan Paketleriniz :</b> <br> '.__($NetGSM_packet_info);
                                    }
                                    if ($NetGSM_credit_info) {
                                        echo '<b>Kalan Krediniz :</b> <br> '.esc_attr($NetGSM_credit_info) .' TL';
                                    }
                                }
                            ?>
                    </td>
                </tr>



                <tr class="Kobikom" <?php if( $sms_provider != 'Kobikom' ) echo 'style="display:none"'?>>
                    <th scope="row" style="width:40%">
                        <hr>
                    </th>
                    <td>
                        <hr>
                    </td>
                    <td>
                        <hr>
                    </td>
                </tr>

                <tr valign="top" class="Kobikom" <?php if( $sms_provider != 'Kobikom' ) echo 'style="display:none"'?>>
                    <th scope="row" style="width:25%">
                        <?php _e( 'Kobikom Bilgileriniz <br> Kobikom API adresinizi girmeniz gerekmektedir.', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <label for="Kobikom_ApiKey" class="label-bold">Kobikom API key anahtariniz</label> <br>
                        <textarea type="text" id="Kobikom_ApiKey" name="Kobikom_ApiKey"  rows="6" ><?php echo esc_attr($Kobikom_ApiKey); ?></textarea>
                    </td> 
                </tr>

                <tr valign="top" class="Kobikom" <?php if ($sms_provider != 'Kobikom') echo 'style="display:none"'?>>
                    <th scope="row" style="width:25%"></th>
                    <td>
                        <label for="KobiKom_Header" class="label-bold">SMS Başlığınız </label> <br>
                        <?php
                        
                                if ($Kobikom_ApiKey) { 
                                    $KobiKom_get_Headers = kargoTR_get_kobikom_headers($Kobikom_ApiKey);
                                    if (!$KobiKom_get_Headers) {
                                        echo 'Kobikom Api Keyiniz yanlis';
                                    } else {
                                       echo '<select name="KobiKom_Header" id="KobiKom_Header">';
                                        foreach ($KobiKom_get_Headers as $key => $value) {
                                        
                                            if ($KobiKom_Header == $value['title']) {
                                                echo '<option selected value="'.$value['title'].'">'.$value['title'].'</option>';
                                            } else {
                                                echo '<option value="'.$value['title'].'">'.$value['title'].'</option>';
                                            }
                                        }
                                        echo '</select>';
                                    }
                                }
                            ?>
                    </td>
                    <td>
                        
                        <?php
                                if ($Kobikom_ApiKey){
                                    $KobiKom_get_Credit = kargoTR_get_kobikom_balance($Kobikom_ApiKey);
                                    echo "Kobikom Paketleriniz : <br> <hr>";
                                    if ($KobiKom_get_Credit) {
                                        foreach ($KobiKom_get_Credit as $key => $value) {
                                            echo $value['name'] . ' : <br> Kalan Kredi ' . $value['amount'] . ' SMS <br> Paketin Son Kullanma Tarihi : ' . $value['finished_at'] . '<br>';
                                        }
                                       
                                    }else
                                    {
                                        echo "Kobikom Api Keyiniz yanlis";
                                    }
                                }


                            ?>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row" style="width:50%">
                        <?php _e( 'Kargo takip URL de gönderilsin mi ? <br> Eğer bu özelliği açarsanız sms boyutunuz muhtemelen daha büyük olacak ve ekstradan kredi harçayacaktır paketinizden.', 'kargoTR' ) ?>
                    </th>
                    <td>
                        <input type="radio" id="yes_url_send" <?php if( $NetGsm_sms_url_send == 'yes' ) echo 'checked'?>
                            name="NetGsm_sms_url_send" value="yes">
                        <label for="yes_url_send">Evet</label><br>
                    </td>
                    <td>
                        <input type="radio" id="noUrlSend" <?php if( $NetGsm_sms_url_send == 'no' ) echo 'checked'?>
                            name="NetGsm_sms_url_send" value="no">
                        <label for="noUrlSend">Hayır</label><br>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

            <script>
                jQuery(document).ready(function ($) {
                    $('input[type=radio][name=sms_provider]').change(function () {
                        if (this.value == 'no') {
                            $('.netgsm').hide();

                        } else if (this.value == 'NetGSM') {
                            $('.netgsm').show(2000);
                            $('.Kobikom').hide();
                            
                        }
                        else if (this.value == 'Kobikom') {
                            $('.Kobikom').show(2000);
                            $('.netgsm').hide();
                        }
                    });

                

                    
                })
            </script>

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


