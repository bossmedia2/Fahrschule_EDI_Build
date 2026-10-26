#!/usr/bin/env bash
# Prüfkette vor dem Build - der einzige Einstieg. Läuft aus jedem Verzeichnis.
#
#   bin/gate.sh              volle Kette
#   bin/gate.sh --schnell    ohne Browserprüfung (sonst alles)
#                            (--no-browser bleibt als gleichbedeutender Schalter)
#
# Reihenfolge ist Absicht: erst was ohne Rendern auffällt (Syntax), dann das
# Rendern selbst, dann Farben, dann was nur im Browser sichtbar wird, zuletzt
# der Build. Ein fehlgeschlagener Schritt bricht nicht ab - die Tabelle am
# Ende zeigt alle Ergebnisse, der Exit-Code ist dann ungleich 0.
#
# Nicht Teil der Kette: bin/edi-bilder.py und sein Test (manuell, siehe
# docs/SETUP.md).
set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME="$ROOT/fahrschule-edi"
PLUGIN="$ROOT/edi-integrationen"
WORK="$(mktemp -d)"
trap 'rm -rf "${WORK:?}"' EXIT
export FS_OUT="$WORK"

SKIP_BROWSER=0
for a in "$@"; do
	case "$a" in
		--schnell|--no-browser) SKIP_BROWSER=1 ;;
		*) echo "Unbekannter Schalter: $a (erlaubt: --schnell)" >&2; exit 2 ;;
	esac
done

ROWS=()          # "Nr|Schritt|Ergebnis|Dauer"
FAILED=0
CUR=""; CUR_NO=""; T0=0
now_ms() { echo $(( $(date +%s%N) / 1000000 )); }
step() { CUR_NO="$1"; CUR="$2"; T0=$(now_ms); printf '\n\033[1m[%s] %s\033[0m\n' "$1" "$2"; }
# done_step <rc> [ergebnis-text]
done_step() {
	local rc="$1" res ms
	ms=$(( $(now_ms) - T0 ))
	if [ "$rc" -eq 0 ]; then res="${2:-ok}"; else res="FEHLER"; FAILED=$((FAILED+1)); printf '  -> %s FEHLGESCHLAGEN\n' "$CUR"; fi
	ROWS+=("$CUR_NO|$CUR|$res|$(printf '%d.%01ds' $((ms/1000)) $(((ms%1000)/100)))")
}

# --- 1 Syntax --------------------------------------------------------------
step 1a "PHP-Syntax (php -l)"
rc=0; n=0
while IFS= read -r f; do
	n=$((n+1)); out=$(php -l "$f" 2>&1) || { echo "$out"; rc=1; }
done < <(find "$THEME" "$PLUGIN" -name '*.php' -type f | sort)
[ "$rc" -eq 0 ] && echo "  ok: $n Dateien ohne Syntaxfehler"
done_step "$rc"

step 1b "JavaScript-Syntax (node --check)"
rc=0
for f in "$THEME"/assets/js/*.js "$ROOT"/bin/checks/*.mjs; do node --check "$f" || rc=1; done
[ "$rc" -eq 0 ] && echo "  ok: Theme-Skripte und Prüfskripte ohne Syntaxfehler"
done_step "$rc"

# --- 2 Struktur, Referenzen, Rendern, Leerbarkeit ---------------------------
step 2a "Gate-Regeln: kein editor_script, Bild-Skript nicht in der Kette"
rc=0
# Block-Skripte nur über enqueue_block_editor_assets (Kommentare zählen nicht).
hits=$(grep -rn "editor_script" "$THEME" --include=*.php --include=*.json | grep -vE "^[^:]+:[0-9]+:\s*(//|\*|#)" || true)
[ -n "$hits" ] && { echo "  FEHLER: editor_script registriert:"; echo "$hits" | sed 's/^/    /'; rc=1; }
# Das Bild-Skript ist Werkzeug, kein Prüfschritt: diese Datei ruft es nicht auf.
pat='edi-bild''er'
calls=$(grep -vE '^\s*#' "${BASH_SOURCE[0]}" | grep -c "$pat" || true)
[ "$calls" -ne 0 ] && { echo "  FEHLER: gate.sh ruft das Bild-Skript auf"; rc=1; }
[ "$rc" -eq 0 ] && echo "  ok: kein editor_script im Theme; Bild-Skript nicht in der Kette"
done_step "$rc"

step 2b "Smoke-Test: alle Blöcke und Seiten rendern"
smoke=$(php "$ROOT/bin/checks/smoke.php" "$THEME" 2>&1); rc=$?
echo "$smoke" | grep -E "^TOTAL|FATAL|EMPTY|notice/warning|^Galerie|^Social|^Hero|^Seitenanlage|^404|!! " | sed 's/^/  /'
echo "$smoke" | grep -qE "FATAL|!! " && rc=1
pages_ok=$(echo "$smoke" | grep -cE "^[a-z0-9-]+ +/.* OK$")
echo "  ok: $pages_ok Seiten vollständig gerendert"
done_step "$rc" "ok ($pages_ok Seiten)"

step 2c "Startseite nach PDF, keine Doppelung, Stammdaten (NAP)"
python3 -I "$ROOT/bin/checks/startseite.py" "$ROOT" "$WORK"; done_step $?

step 2d "Referenzen: Blöcke, Icons, Assets, CSS-Klassen, Links"
python3 -I "$ROOT/bin/checks/refs.py" "$ROOT"; done_step $?

step 2e "Struktur: Vorlagen, Startmuster, Hierarchie"
python3 -I "$ROOT/bin/checks/structure.py" "$ROOT"; done_step $?

step 2f "Formulare: Contact Form 7 + Begleit-Plugin"
php "$ROOT/bin/checks/formulare.php" "$THEME" "$PLUGIN"; done_step $?

step 2g "Editierbarkeit: Felder leerbar, Wiederholfelder bearbeitbar"
php "$ROOT/bin/checks/emptiable.php" "$THEME" 2>&1 | grep -E "^(Textfelder|  ok|  FEHLER|Wiederholfelder|LEERBARKEIT)" | sed 's/^/  /'
done_step "${PIPESTATUS[0]}"

step 2h "Doku: Block- und Seitentabelle aktuell"
php "$ROOT/bin/docs-tabellen.php" --pruefen 2>/dev/null; done_step $?

# --- 3 Farben ----------------------------------------------------------------
step 3a "Farbkontexte: Tokens, kein Pink Tief, Hover nur aus Kontext"
python3 -I "$ROOT/bin/checks/tokens.py" "$ROOT"; done_step $?

if [ -f "$ROOT/bin/checks/kontrast.py" ]; then
	step 3b "Kontrast rechnerisch je Fläche (WCAG AA)"
	python3 -I "$ROOT/bin/checks/kontrast.py" "$ROOT"; done_step $?
fi

if [ -f "$ROOT/bin/checks/flaechen.py" ]; then
	step 3c "Flächenfolge je Seite (nie dreimal Vollpink)"
	python3 -I "$ROOT/bin/checks/flaechen.py" "$ROOT" "$WORK"; done_step $?
fi

# --- 4 Browser ---------------------------------------------------------------
step 4 "Browser: 390/768/1024/1280/1512/1920 px, Menüs offen, Kontrast, Fokus"
if [ "$SKIP_BROWSER" -eq 1 ]; then
	echo "  übersprungen (--schnell)"
	done_step 0 "übersprungen"
elif ! node -e "require.resolve('playwright')" >/dev/null 2>&1 \
     && [ ! -d /opt/node-tools/node_modules/playwright ]; then
	echo "  FEHLER: playwright nicht installiert (npm i -D playwright) – oder --schnell"
	done_step 1
else
	php "$ROOT/bin/checks/mkpages.php" "$THEME"
	NODE_PATH="${NODE_PATH:-/opt/node-tools/node_modules}" node "$ROOT/bin/checks/browser.mjs" "$WORK"
	done_step $?
fi

# --- 5 Tailwind ----------------------------------------------------------------
step 5 "Tailwind (nur wenn die Quelle neuer ist)"
tw_src=$(find "$THEME" -maxdepth 3 \( -name 'tailwind*.css' -o -name 'tailwind.config.*' \) 2>/dev/null | head -1)
if [ -z "$tw_src" ]; then
	echo "  entfällt – keine Tailwind-Quelle im Theme (CSS ist handgeschrieben: assets/css/main.css)"
	done_step 0 "entfällt"
elif [ "$tw_src" -nt "$THEME/assets/css/main.css" ]; then
	echo "  FEHLER: $tw_src ist neuer als main.css – Tailwind-Build ausführen"
	done_step 1
else
	echo "  ok: main.css ist aktuell"
	done_step 0
fi

# --- 6 Build -----------------------------------------------------------------
step 6 "Build: Theme- und Plugin-ZIP"
bash "$ROOT/bin/build-zip.sh" >"$WORK/build.log" 2>&1; rc=$?
if [ "$rc" -eq 0 ]; then
	for z in fahrschule-edi edi-integrationen; do
		[ -f "$ROOT/dist/$z.zip" ] || { echo "  FEHLER: dist/$z.zip fehlt"; rc=1; }
	done
fi
if [ "$rc" -eq 0 ]; then
	bytes=$(stat -c%s "$ROOT/dist/fahrschule-edi.zip"); pbytes=$(stat -c%s "$ROOT/dist/edi-integrationen.zip")
	res=$(printf 'Theme %.2f MB, Plugin %d KB' "$(echo "scale=4; $bytes/1048576" | bc)" $((pbytes/1024)))
	echo "  ok: dist/fahrschule-edi.zip und dist/edi-integrationen.zip ($res, Grenze Theme 2.00 MB)"
	done_step 0 "$res"
else
	tail -12 "$WORK/build.log" | sed 's/^/  /'
	done_step 1
fi

# --- 7 Zusammenfassung -------------------------------------------------------
echo
printf '\033[1m%-4s %-62s %-28s %8s\033[0m\n' "Nr" "Schritt" "Ergebnis" "Dauer"
for r in "${ROWS[@]}"; do
	IFS='|' read -r no name res dur <<<"$r"
	if [ "$res" = "FEHLER" ]; then col='\033[31m'; else col='\033[32m'; fi
	printf "%-4s %-62s ${col}%-28s\033[0m %8s\n" "$no" "${name:0:62}" "$res" "$dur"
done
echo
if [ "$FAILED" -eq 0 ]; then
	printf '\033[32mGATE: bestanden\033[0m\n'
	exit 0
fi
printf '\033[31mGATE: fehlgeschlagen (%d Schritte)\033[0m\n' "$FAILED"
exit 1
