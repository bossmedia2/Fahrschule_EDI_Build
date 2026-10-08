#!/usr/bin/env bash
# Prüfkette vor dem Build. Bricht beim ersten fehlgeschlagenen Schritt ab.
#
#   bin/gate.sh            volle Kette
#   bin/gate.sh --no-browser   ohne Browserprüfung (z. B. ohne Playwright)
#
# Reihenfolge ist Absicht: erst was ohne Rendern auffällt (Syntax), dann das
# Rendern selbst, dann was nur am gerenderten Dokument sichtbar wird.
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME="$ROOT/fahrschule-edi"
WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
export FS_OUT="$WORK"

SKIP_BROWSER=0
[ "${1:-}" = "--no-browser" ] && SKIP_BROWSER=1

STEP=0
FAILED=()
step() { STEP=$((STEP+1)); printf '\n\033[1m[%d] %s\033[0m\n' "$STEP" "$1"; }
check() { if [ "$1" -ne 0 ]; then FAILED+=("$2"); printf '  -> %s FEHLGESCHLAGEN\n' "$2"; fi; }

# --- 1 ---------------------------------------------------------------------
step "PHP-Syntax"
rc=0
while IFS= read -r f; do
	out=$(php -l "$f" 2>&1) || { echo "$out"; rc=1; }
done < <(find "$ROOT/fahrschule-edi" "$ROOT/edi-integrationen" -name '*.php' -type f | sort)
[ "$rc" -eq 0 ] && echo "  ok: $(find "$ROOT/fahrschule-edi" "$ROOT/edi-integrationen" -name '*.php' | wc -l) Dateien ohne Syntaxfehler"
check "$rc" "PHP-Syntax"

# --- 2 ---------------------------------------------------------------------
step "JavaScript-Syntax"
rc=0
for f in "$THEME"/assets/js/*.js; do node --check "$f" || rc=1; done
[ "$rc" -eq 0 ] && echo "  ok: $(ls "$THEME"/assets/js/*.js | wc -l) Dateien ohne Syntaxfehler"
check "$rc" "JavaScript-Syntax"

# --- 3 ---------------------------------------------------------------------
step "Smoke-Test: alle Blöcke und Seiten rendern"
smoke=$(php "$ROOT/bin/checks/smoke.php" "$THEME" 2>&1); rc=$?
echo "$smoke" | grep -E "^TOTAL|FATAL|EMPTY|notice/warning" | sed 's/^/  /'
echo "$smoke" | grep -qE "FATAL|!! " && rc=1
echo "$smoke" | tail -40 | grep -E "^(home|[a-z-]+) +/" >/dev/null || true
pages_ok=$(echo "$smoke" | grep -cE "^[a-z-]+ +/.* OK$")
echo "  ok: $pages_ok Seiten vollständig gerendert"
check "$rc" "Smoke-Test"

# --- 4 ---------------------------------------------------------------------
step "Referenzen: Blöcke, Icons, Assets, CSS-Klassen, Keyframes, Farben"
python3 -I "$ROOT/bin/checks/refs.py" "$ROOT"; check $? "Referenzprüfung"

# --- 5 ---------------------------------------------------------------------
if [ "$SKIP_BROWSER" -eq 0 ]; then
	step "Browser: Navigationslayout, Zoom, Kontrast, Fokusring"
	if ! node -e "require.resolve('playwright')" >/dev/null 2>&1 \
	   && [ ! -d /opt/node-tools/node_modules/playwright ]; then
		echo "  übersprungen: playwright nicht installiert (npm i -D playwright)"
	else
		php "$ROOT/bin/checks/mkpages.php" "$THEME"
		NODE_PATH="${NODE_PATH:-/opt/node-tools/node_modules}" \
			node "$ROOT/bin/checks/browser.mjs" "$WORK"
		check $? "Browserprüfung"
	fi
fi

# --- 6 ---------------------------------------------------------------------
step "Build und Archivgröße"
bash "$ROOT/bin/build-zip.sh" >/dev/null 2>&1; rc=$?
if [ "$rc" -eq 0 ]; then
	bytes=$(stat -c%s "$ROOT/dist/fahrschule-edi.zip")
	printf '  ok: dist/fahrschule-edi.zip = %.2f MB (Grenze 2.00 MB)\n' "$(echo "scale=4; $bytes/1048576" | bc)"
else
	bash "$ROOT/bin/build-zip.sh" 2>&1 | tail -12 | sed 's/^/  /'
fi
check "$rc" "Archivgröße"

# --- Ergebnis --------------------------------------------------------------
echo
if [ "${#FAILED[@]}" -eq 0 ]; then
	printf '\033[32mGATE: bestanden\033[0m\n'
	exit 0
fi
printf '\033[31mGATE: fehlgeschlagen (%d)\033[0m\n' "${#FAILED[@]}"
printf '  - %s\n' "${FAILED[@]}"
exit 1
