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
}