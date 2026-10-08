#!/usr/bin/env bash
# Baut aus fahrschule-edi/ und edi-integrationen/ je ein installierbares
# ZIP (Theme bzw. Begleit-Plugin) für "Design -> Themes -> Theme
# hochladen" bzw. "Plugins -> Installieren".
#
# Ergebnis: dist/fahrschule-edi.zip, dist/edi-integrationen.zip
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="$ROOT/dist"

rm -rf "$DIST"
mkdir -p "$DIST"

zip_folder() {
	local src_name="$1"
	local out_name="$2"
	( cd "$ROOT" && zip -rq "$DIST/$out_name.zip" "$src_name" \
		-x "$src_name/.git/*" \
		-x "$src_name/.DS_Store" )
}

zip_folder "fahrschule-edi" "fahrschule-edi"
files=$(unzip -l "$DIST/fahrschule-edi.zip" | tail -1 | awk '{print $2}')
size=$(du -k "$DIST/fahrschule-edi.zip" | cut -f1)
echo "Fahrschule Edi (B2C)"
echo "   Archiv:  dist/fahrschule-edi.zip  ($files Dateien, ${size} KB)"

if [ -d "$ROOT/edi-integrationen" ]; then
	zip_folder "edi-integrationen" "edi-integrationen"
	files=$(unzip -l "$DIST/edi-integrationen.zip" | tail -1 | awk '{print $2}')
	size=$(du -k "$DIST/edi-integrationen.zip" | cut -f1)
	echo
	echo "Edi Integrationen (Begleit-Plugin)"
	echo "   Archiv:  dist/edi-integrationen.zip  ($files Dateien, ${size} KB)"
fi

echo
echo "Einspielen: Design -> Themes -> Theme hinzufügen -> Theme hochladen"
echo "Optional:   edi-integrationen.zip unter Plugins -> Installieren einspielen"
echo "            (nur nötig für Odoo/Matomo-Anbindung)."
