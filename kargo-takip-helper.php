<?php

function kargoTR_get_company_name($tracking_company){
    if ($tracking_company == 'ptt') {
        return "PTT Kargo";
    }
    if ($tracking_company == 'yurtici') {
        return "Yurtiçi Kargo";
    }
    if ($tracking_company == 'aras') {
        return "Aras Kargo";
    }
    if ($tracking_company == 'mng') {
        return "MNG Kargo";
    }
    if ($tracking_company == 'horoz') {
        return "Horoz Kargo";
    }
    if ($tracking_company == 'ups') {
        return "UPS Kargo";
    }
    if ($tracking_company == 'surat') {
        return "Sürat Kargo";
    }
    if ($tracking_company == 'filo') {
        return "Filo Kargo";
    }
    if ($tracking_company == 'tnt') {
        return "TNT Kargo";
    }
    if ($tracking_company == 'dhl') {
        return "DHL Kargo";
    }
    if ($tracking_company == 'fedex') {
        return "Fedex Kargo";
    }
    if ($tracking_company == 'foodman') {
        return "FoodMan Kargo";
    }
    if ($tracking_company == 'postman'){
        return "Postman Kargo";
    }
    if ($tracking_company == 'iyi'){
        return "İyi Kargo";
    }
    if ($tracking_company == 'tex'){
        return "Trendyol Express";
    }
    if ($tracking_company == 'hepsijet'){
        return "HepsiJET";
    }
}

function kargoTR_getCargoTrack($tracking_company = NULL, $tracking_code = NULL)
{
    // Genel fonksiyon içine alarak SMS gönderirken de kod fazlalılığı yapmamak için :)
    if ($tracking_company == 'ptt') {
        $cargoTrackingUrl = 'https://gonderitakip.ptt.gov.tr/Track/Verify?q=' . $tracking_code;
    }
    if ($tracking_company == 'yurtici') {
        $cargoTrackingUrl = 'https://www.yurticikargo.com/tr/online-servisler/gonderi-sorgula?code=' . $tracking_code;
    }
    if ($tracking_company == 'aras') {
        $cargoTrackingUrl = 'https://www.araskargo.com.tr/trmobile/cargo_tracking_detail.aspx?query=1&querydetail=2&ref_no=&seri_no=&irs_no=&kargo_takip_no=' . $tracking_code;
    }
    if ($tracking_company == 'mng') {
        $cargoTrackingUrl = 'http://service.mngkargo.com.tr/iactive/popup/KargoTakip/link1.asp?k=' . $tracking_code;
    }
    if ($tracking_company == 'horoz') {
        $cargoTrackingUrl = 'https://app3.horoz.com.tr/wsKurumsal/_genel/frmGonderiTakip.aspx?lng=tr';
    }
    if ($tracking_company == 'ups') {
        $cargoTrackingUrl = 'https://www.ups.com.tr/WaybillSorgu.aspx?Waybill=' . $tracking_code;
    }
    if ($tracking_company == 'surat') {
        $cargoTrackingUrl = 'https://www.suratkargo.com.tr/KargoTakip/?kargotakipno=' . $tracking_code;
    }
    if ($tracking_company == 'filo') {
        $cargoTrackingUrl = 'http://filloweb.fillo.com.tr/GonderiTakip';
    }
    if ($tracking_company == 'tnt') {
        $cargoTrackingUrl = 'https://www.tnt.com/express/tr_tr/site/shipping-tools/tracking.html?searchType=con&cons=' . $tracking_code;
    }
    if ($tracking_company == 'dhl') {
        $cargoTrackingUrl = 'https://www.dhl.com/tr-tr/home/tracking.html?tracking-id=' . $tracking_code;
    }
    if ($tracking_company == 'fedex') {
        $cargoTrackingUrl = 'https://www.fedex.com/fedextrack/?action=track&tracknumbers=' . $tracking_code . '&locale=tr_TR&cntry_code=us';
    }
    if ($tracking_company == 'foodman') {
        $cargoTrackingUrl = 'https://www.foodman.online/GonderiSorgu.aspx?gonderino=' . $tracking_code;
    }
    if ($tracking_company == 'postrans') {
        $cargoTrackingUrl = 'http://85.99.122.231/hareket.asp?har_kod=' . $tracking_code;
    }
    if ($tracking_company == 'iyi') {
        $cargoTrackingUrl = 'https://www.geowix.com/kargom-nerede?tracking_code=' . $tracking_code . '&p=1fbe7c33-3226-4aad-aec3-850dc2487597&pfix=';
    }
    if ($tracking_company == 'tex') {
        $cargoTrackingUrl = 'https://kargotakip.trendyol.com/?orderNumber=' . $tracking_code;
    }
    if ($tracking_company == 'hepsijet') {
        $cargoTrackingUrl = 'https://www.hepsijet.com/gonderi-takibi/' . $tracking_code;
    }

    return $cargoTrackingUrl;
}
