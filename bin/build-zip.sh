#!/usr/bin/env bash
# Baut aus fahrschule-edi/ und edi-integrationen/ je ein installierbares
# ZIP (Theme bzw. Begleit-Plugin) für "Design -> Themes -> Theme
# hochladen" bzw. "Plugins -> Installieren".
#
# Ergebnis: dist/fahrschule-edi.zip, dist/edi-integrationen.zip
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="$ROOT/dist"

# Obergrenze für das Theme-Archiv. Viele Hoster und wp-admin selbst begrenzen
# den Upload; außerdem ist jede überflüssige Datei im Theme eine, die bei jedem
# Deployment mitgeschleppt wird. Der Build bricht ab, statt die Grenze
# stillschweigend zu reißen.
MAX_THEME_BYTES=$((2 * 1024 * 1024))

rm -rf "$DIST"
mkdir -p "$DIST"

zip_folder() {
	local src_name="$1"
	local out_name="$2"
	# Dokumentation, Entwicklungsreste und Betriebssystem-Müll gehören ins
	# Repository, nicht in das Archiv, das auf dem Server landet.
	( cd "$ROOT" && zip -rq "$DIST/$out_name.zip" "$src_name" \
		-x "$src_name/.git/*" \
		-x "$src_name/.DS_Store" \
		-x "$src_name/**/.DS_Store" \
		-x "$src_name/*.md" \
		-x "$src_name/**/*.md" \
		-x "$src_name/docs/*" \
		-x "$src_name/**/*.map" \
		-x "$src_name/**/Thumbs.db" )
}

zip_folder "fahrschule-edi" "fahrschule-edi"
theme_zip="$DIST/fahrschule-edi.zip"
files=$(unzip -l "$theme_zip" | tail -1 | awk '{print $2}')
bytes=$(stat -c%s "$theme_zip")
echo "Fahrschule Edi (B2C)"
printf '   Archiv:  dist/fahrschule-edi.zip  (%s Dateien, %.2f MB)\n' "$files" "$(echo "scale=4; $bytes/1048576" | bc)"

if [ "$bytes" -ge "$MAX_THEME_BYTES" ]; then
	printf '\nFEHLER: Das Theme-Archiv ist %.2f MB groß, erlaubt sind maximal %.2f MB.\n' \
		"$(echo "scale=4; $bytes/1048576" | bc)" "$(echo "scale=4; $MAX_THEME_BYTES/1048576" | bc)" >&2
	echo "Die größten Dateien im Archiv:" >&2
	unzip -l "$theme_zip" | sort -rn | head -10 >&2
	exit 1
fi
printf '   Grenze:  %.2f MB eingehalten (%.2f MB Reserve)\n' \
	"$(echo "scale=4; $MAX_THEME_BYTES/1048576" | bc)" \
	"$(echo "scale=4; ($MAX_THEME_BYTES-$bytes)/1048576" | bc)"

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
echo "            (Formulare in Contact Form 7, WP Job Manager, Matomo)."
