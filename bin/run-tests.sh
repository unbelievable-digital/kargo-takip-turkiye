#!/usr/bin/env bash
#
# Kargo Takip Türkiye test paketi
#
# Testleri wp-env (Docker) üzerinde gerçek bir WordPress + WooCommerce
# kurulumunda çalıştırır. Yerelde kullanım:
#
#   ./bin/run-tests.sh          # ortamı başlatır, testleri çalıştırır
#   ./bin/run-tests.sh --keep   # test sonrası ortamı açık bırakır
#
set -euo pipefail

cd "$(dirname "$0")/.."

KEEP_ENV="${1:-}"
WP_ENV="npx --yes @wordpress/env"

echo "==> PHP söz dizimi denetimi"
FAILED=0
for file in *.php mail-template/*.php tests/*.php; do
    [ -e "$file" ] || continue
    if ! php -l "$file" > /dev/null; then
        echo "Söz dizimi hatası: $file"
        FAILED=1
    fi
done
[ "$FAILED" -eq 0 ] || exit 1
echo "Tüm PHP dosyaları geçti."

echo "==> Test ortamı başlatılıyor"
$WP_ENV start

echo "==> Eklentiler etkinleştiriliyor"
# WooCommerce zip'i klasör adını koruyarak kurulduğu için tüm eklentiler etkinleştirilir.
# Eklenti başlığındaki "Requires Plugins" nedeniyle WooCommerce önce aktif olmalı.
$WP_ENV run cli wp plugin activate --all

echo "==> Dokan Lite kuruluyor (uyumluluk testleri için)"
$WP_ENV run cli wp plugin install dokan-lite --activate --force || echo "Dokan kurulamadı, Dokan testleri atlanacak."

echo "==> WooCommerce kurulum sihirbazı atlanıyor"
$WP_ENV run cli wp option update woocommerce_onboarding_profile '{"skipped":true}' --format=json > /dev/null

echo "==> Etkin eklentiler"
$WP_ENV run cli wp plugin list --status=active --field=name

run_suite() {
    local file="$1"
    echo ""
    echo "==> $file"
    $WP_ENV run cli wp eval-file "wp-content/plugins/kargo-takip-turkiye/$file"
}

EXIT_CODE=0
for suite in tests/test-*.php; do
    run_suite "$suite" || EXIT_CODE=1
done

if [ "$KEEP_ENV" != "--keep" ]; then
    echo ""
    echo "==> Test ortamı durduruluyor"
    $WP_ENV stop > /dev/null
fi

if [ "$EXIT_CODE" -ne 0 ]; then
    echo ""
    echo "Testler başarısız."
    exit 1
fi

echo ""
echo "Tüm testler geçti."
