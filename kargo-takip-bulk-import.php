<?php

function kargoTR_bulk_import_page() {
    // Handle CSV Upload
    if (isset($_POST['kargoTR_import_csv_nonce']) && wp_verify_nonce($_POST['kargoTR_import_csv_nonce'], 'kargoTR_import_csv')) {
        kargoTR_handle_csv_upload();
    }

    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-upload"></span> Toplu Kargo Takip Girişi (Excel/CSV)</h1>
        
        <div class="card" style="max-width: 100%; margin-top: 20px; padding: 20px;">
            <h2>CSV Dosyası Yükle</h2>
            <p>Günlük kargo gönderimlerinizi toplu olarak sisteme yükleyebilirsiniz. Dosya formatı aşağıdaki gibi olmalıdır:</p>
            
            <div style="background: #f0f0f1; padding: 15px; border-left: 4px solid #0073aa; margin-bottom: 20px;">
                <strong>Format:</strong> Sipariş No, Kargo Firması, Takip Numarası<br>
                <strong>Örnek:</strong> 12345, Yurtici, 123456789012
            </div>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('kargoTR_import_csv', 'kargoTR_import_csv_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="csv_file">CSV Dosyası Seçin</label></th>
                        <td>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            <p class="description">Lütfen .csv uzantılı dosya yükleyiniz.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Ayarlar</th>
                        <td>
                            <fieldset>
                                <label for="send_sms">
                                    <input type="checkbox" name="send_sms" id="send_sms" value="yes" checked>
                                    SMS Gönder (Ayarlıysa)
                                </label>
                                <br>
                                <label for="send_email">
                                    <input type="checkbox" name="send_email" id="send_email" value="yes" checked>
                                    E-posta Gönder (Ayarlıysa)
                                </label>
                                <br>
                                <label for="complete_order">
                                    <input type="checkbox" name="complete_order" id="complete_order" value="yes" checked>
                                    Sipariş Durumunu "Tamamlandı" veya "Kargoya Verildi" yap
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Dosyayı Yükle ve İşle">
                </p>
            </form>
        </div>

        <div class="card" style="max-width: 100%; margin-top: 20px; padding: 20px;">
            <h3>Kargo Firma Kodları</h3>
            <p>CSV dosyasında kullanabileceğiniz kargo firması isimleri (büyük/küçük harf duyarlı değildir):</p>
            <p>
                <?php
                $cargoes = kargoTR_get_all_cargoes(true);
                $cargo_names = array_keys($cargoes);
                echo esc_html(implode(', ', $cargo_names));
                ?>
            </p>
        </div>
    </div>
    <?php
}

function kargoTR_handle_csv_upload() {
    // Security: Capability check
    if (!current_user_can('manage_options')) {
        echo '<div class="notice notice-error"><p>Bu işlem için yetkiniz bulunmuyor.</p></div>';
        return;
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="notice notice-error"><p>Dosya yüklenirken bir hata oluştu.</p></div>';
        return;
    }

    // Security: Validate file extension
    $file_name = isset($_FILES['csv_file']['name']) ? sanitize_file_name($_FILES['csv_file']['name']) : '';
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($file_ext !== 'csv') {
        echo '<div class="notice notice-error"><p>Sadece .csv uzantılı dosyalar kabul edilmektedir.</p></div>';
        return;
    }

    // Security: Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($_FILES['csv_file']['size'] > $max_size) {
        echo '<div class="notice notice-error"><p>Dosya boyutu 5MB\'dan büyük olamaz.</p></div>';
        return;
    }

    // Security: Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $_FILES['csv_file']['tmp_name']);
    finfo_close($finfo);

    $allowed_mimes = array('text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel');
    if (!in_array($mime_type, $allowed_mimes)) {
        echo '<div class="notice notice-error"><p>Geçersiz dosya türü. Sadece CSV dosyaları kabul edilmektedir.</p></div>';
        return;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");

    if ($handle === FALSE) {
        echo '<div class="notice notice-error"><p>Dosya açılamadı.</p></div>';
        return;
    }

    $success_count = 0;
    $error_count = 0;
    $errors = array();

    $send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] === 'yes';
    $send_email = isset($_POST['send_email']) && $_POST['send_email'] === 'yes';
    $complete_order = isset($_POST['complete_order']) && $_POST['complete_order'] === 'yes';

    // Get all cargo companies for validation (lowercase keys)
    $cargoes = kargoTR_get_all_cargoes(true);
    $cargoes_lower = array_change_key_case($cargoes, CASE_LOWER);

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Skip empty rows
        if (empty($data[0])) continue;

        // Clean data
        $order_id = intval(trim($data[0]));
        $cargo_company_input = trim($data[1]);
        $tracking_code = trim($data[2]);

        // Validate Order
        $order = wc_get_order($order_id);
        if (!$order) {
            $error_count++;
            $errors[] = "Sipariş ID {$order_id} bulunamadı.";
            continue;
        }

        // Validate Cargo Company
        $cargo_company_key = '';
        $input_lower = strtolower($cargo_company_input);
        
        // Try to match input with keys
        foreach ($cargoes as $key => $value) {
            if (strtolower($key) == $input_lower || strtolower($value) == $input_lower) {
                $cargo_company_key = $key;
                break;
            }
        }

        // If not found, check if it's a valid key directly
        if (empty($cargo_company_key) && array_key_exists($input_lower, $cargoes_lower)) {
             // Find original case key
             foreach ($cargoes as $key => $value) {
                 if (strtolower($key) == $input_lower) {
                     $cargo_company_key = $key;
                     break;
                 }
             }
        }

        if (empty($cargo_company_key)) {
            // Default to 'diger' or keep empty? Let's skip if invalid to be safe
            // Or maybe user entered "Yurtici Kargo" instead of "Yurtici"
            // Let's try to be lenient, if not found, use input as is if it's not empty? 
            // No, plugin logic relies on keys.
            $error_count++;
            $errors[] = "Sipariş {$order_id}: Geçersiz kargo firması '{$cargo_company_input}'.";
            continue;
        }

        // Update Order Meta (HPOS uyumlu)
        $order->update_meta_data('tracking_company', $cargo_company_key);
        $order->update_meta_data('tracking_code', $tracking_code);

        // Save specific timestamp for statistics
        $order->update_meta_data('_kargo_takip_timestamp', current_time('mysql'));
        $order->save();

        // Add Note
        $order->add_order_note(sprintf('Toplu yükleme ile kargo bilgisi girildi. Firma: %s, Takip No: %s', $cargo_company_key, $tracking_code));

        // Update Status
        if ($complete_order) {
            // Check if 'wc-kargo-verildi' exists, otherwise use 'completed'
            $statuses = wc_get_order_statuses();
            if (array_key_exists('wc-kargo-verildi', $statuses)) {
                $order->update_status('kargo-verildi');
            } else {
                $order->update_status('completed');
            }
        }

        // Trigger Notifications
        if ($send_email) {
            do_action('order_ship_mail', $order_id);
        }

        if ($send_sms) {
            $sms_provider = get_option('sms_provider');
            if ($sms_provider == 'NetGSM') {
                do_action('order_send_sms', $order_id);
            } elseif ($sms_provider == 'Kobikom') {
                do_action('order_send_sms_kobikom', $order_id);
            }
        }

        $success_count++;
    }

    fclose($handle);

    // Show Results
    echo '<div class="notice notice-success is-dismissible"><p>İşlem Tamamlandı. Başarılı: <strong>' . $success_count . '</strong></p></div>';
    
    if ($error_count > 0) {
        echo '<div class="notice notice-warning is-dismissible"><p>Bazı satırlar işlenemedi (' . $error_count . ' hata):</p>';
        echo '<ul style="list-style: disc; margin-left: 20px;">';
        foreach ($errors as $err) {
            echo '<li>' . esc_html($err) . '</li>';
        }
        echo '</ul></div>';
    }
}
