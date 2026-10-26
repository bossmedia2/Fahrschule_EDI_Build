#!/usr/bin/env bash
# Prüfkette vor dem Build - der einzige Einstieg. Läuft aus jedem Verzeichnis.
#
#   bin/gate.sh              volle Kette
#   bin/gate.sh --schnell    ohne Browserprüfung (sonst alles)
#                            (--no-browser bleibt als gleichbedeutender Schalter)
#
# Drei Ergebnisse je Schritt: ok · übersprungen (mit Grund) · FEHLER.
# Schritt 0 prüft zuerst, was die Kette braucht (PHP, Python, Node, Build-
# Werkzeuge, Playwright samt Browser). Fehlt etwas, nennt er den Befehl zum
# Nachrüsten, und die Schritte, die davon abhängen, laufen gar nicht erst -
# sie stehen als „übersprungen“ in der Tabelle, nicht als Fehler. Ebenso
# Folgeschritte eines fehlgeschlagenen Schritts (Startseite, Flächenfolge
# und Browser brauchen die Seiten aus dem Smoke-Test).
#
# Der Exit-Code ist nur bei echten Fehlern ungleich 0. Ein Fehler bricht die
# Kette nicht ab - die Tabelle am Ende zeigt alles.
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
export NODE_PATH="${NODE_PATH-/opt/node-tools/node_modules}"

SCHNELL=0
for a in "$@"; do
	case "$a" in
		--schnell|--no-browser) SCHNELL=1 ;;
		*) echo "Unbekannter Schalter: $a (erlaubt: --schnell)" >&2; exit 2 ;;
	esac
done

declare -A RES        # Schritt bzw. Voraussetzung -> ok | skip | fail
declare -A WHY        # Voraussetzung -> Text, warum sie fehlt
ROWS=()               # "Nr|Schritt|Zustand|Ergebnis|Dauer"
SKIPS=()
FAILED=0
CUR=""; CUR_NO=""; T0=0
now_ms() { echo $(( $(date +%s%N) / 1000000 )); }
dauer() { local ms=$(( $(now_ms) - T0 )); printf '%d.%01ds' $((ms/1000)) $(((ms%1000)/100)); }

# begin <nr> <name> [abhängigkeit …] - 0 = laufen, 1 = übersprungen
begin() {
	CUR_NO="$1"; CUR="$2"; shift 2; T0=$(now_ms)
	printf '\n\033[1m[%s] %s\033[0m\n' "$CUR_NO" "$CUR"
	local d
	for d in "$@"; do
		case "${RES[$d]:-}" in
			ok) ;;
			fail) skip "$d fehlgeschlagen"; return 1 ;;
			skip) if [ -n "${WHY[$d]:-}" ]; then skip "${WHY[$d]}"; else skip "$d übersprungen"; fi; return 1 ;;
			*) skip "$d nicht gelaufen"; return 1 ;;
		esac
	done
	return 0
}
skip() {
	echo "  übersprungen: $1"
	RES[$CUR_NO]=skip
	ROWS+=("$CUR_NO|$CUR|skip|übersprungen ($1)|$(dauer)")
	SKIPS+=("$CUR_NO $CUR – $1")
}
# finish <rc> [ergebnis-text]
finish() {
	local rc="$1"
	if [ "$rc" -eq 0 ]; then
		RES[$CUR_NO]=ok; ROWS+=("$CUR_NO|$CUR|ok|${2:-ok}|$(dauer)")
	else
		RES[$CUR_NO]=fail; FAILED=$((FAILED+1))
		ROWS+=("$CUR_NO|$CUR|fail|FEHLER|$(dauer)")
		printf '  -> %s FEHLGESCHLAGEN\n' "$CUR"
	fi
}
# need <schlüssel> <ok?> <was fehlt> <befehl>
need() {
	if [ "$2" -eq 0 ]; then RES[$1]=ok; echo "  ok: $3"
	else RES[$1]=skip; WHY[$1]="$3 fehlt"; HINWEISE=$((HINWEISE+1)); printf '  FEHLT: %s\n         nachrüsten: %s\n' "$3" "$4"; fi
}

# --- 0 Voraussetzungen ---------------------------------------------------------
begin 0 "Voraussetzungen der Prüfkette"
HINWEISE=0
php_ok=1; phpver=""
if command -v php >/dev/null 2>&1; then
	phpver=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null)
	php -r 'exit(version_compare(PHP_VERSION,"8.0",">=")?0:1);' 2>/dev/null && php_ok=0
fi
need php "$php_ok" "PHP ≥ 8.0 (CLI)${phpver:+, gefunden $phpver}" "sudo apt install php-cli"
if [ "$php_ok" -eq 0 ]; then
	# mbstring: das Theme braucht es nicht (Rückfall, Schritt 2k beweist es).
	# Gemeldet wird es trotzdem - ein Hoster ohne mbstring ist ein Hinweis wert.
	if php -r 'exit(extension_loaded("mbstring")?0:1);'; then
		echo "  ok: PHP-Erweiterung mbstring"
	else
		HINWEISE=$((HINWEISE+1))
		printf '  HINWEIS: PHP-Erweiterung mbstring fehlt – das Theme läuft ohne (Rückfall, Schritt 2k), die Kette auch.\n         nachrüsten: sudo apt install php%s-mbstring\n' "$phpver"
	fi
	php -r 'exit(function_exists("json_encode")?0:1);' || { echo "  FEHLT: PHP-JSON"; RES[php]=skip; WHY[php]="PHP-JSON fehlt"; }
fi
py_ok=1; python3 -c 'import sys; sys.exit(0 if sys.version_info >= (3, 8) else 1)' 2>/dev/null && py_ok=0
need python "$py_ok" "Python ≥ 3.8" "sudo apt install python3"
node_ok=1; command -v node >/dev/null 2>&1 && node_ok=0
need node "$node_ok" "Node.js" "https://nodejs.org (LTS) oder: sudo apt install nodejs"
b_ok=0; fehlt=""
for t in zip unzip bc; do command -v "$t" >/dev/null 2>&1 || { b_ok=1; fehlt="$fehlt $t"; }; done
need build "$b_ok" "Build-Werkzeuge zip, unzip, bc${fehlt:+ (fehlt:$fehlt)}" "sudo apt install zip unzip bc"
# Playwright: Paket und Browser getrennt - die Befehle sind verschiedene.
if [ "$node_ok" -eq 0 ]; then
	pw=$(cd "$ROOT" && node -e '
		const c=["playwright",...(process.env.NODE_PATH||"").split(":").filter(Boolean).map(d=>d+"/playwright")];
		for (const p of c) { try { const {chromium}=require(p); process.stdout.write(require("fs").existsSync(chromium.executablePath())?"ok":"browser"); process.exit(0); } catch(e){} }
		process.stdout.write("paket");' 2>/dev/null)
	case "$pw" in
		ok) RES[playwright]=ok; echo "  ok: Playwright samt Chromium" ;;
		browser) need playwright 1 "Chromium für Playwright (Paket ist da)" "npx playwright install chromium" ;;
		*) need playwright 1 "npm-Paket playwright" "npm i -D playwright && npx playwright install chromium" ;;
	esac
else
	RES[playwright]=skip; WHY[playwright]="Node.js fehlt"
fi
if [ "$SCHNELL" -eq 1 ]; then RES[browser]=skip; WHY[browser]="--schnell"; else RES[browser]=ok; fi
finish 0 "$( [ "$HINWEISE" -eq 0 ] && echo ok || echo "ok, $HINWEISE Hinweis(e)" )"

# --- 1 Syntax --------------------------------------------------------------
if begin 1a "PHP-Syntax (php -l)" php; then
	rc=0; n=0
	while IFS= read -r f; do
		n=$((n+1)); out=$(php -l "$f" 2>&1) || { echo "$out"; rc=1; }
	done < <(find "$THEME" "$PLUGIN" -name '*.php' -type f | sort)
	[ "$rc" -eq 0 ] && echo "  ok: $n Dateien ohne Syntaxfehler"
	finish "$rc"
fi

if begin 1b "JavaScript-Syntax (node --check)" node; then
	rc=0
	for f in "$THEME"/assets/js/*.js "$ROOT"/bin/checks/*.mjs; do node --check "$f" || rc=1; done
	[ "$rc" -eq 0 ] && echo "  ok: Theme-Skripte und Prüfskripte ohne Syntaxfehler"
	finish "$rc"
fi

# --- 2 Struktur, Referenzen, Rendern, Leerbarkeit ---------------------------
if begin 2a "Gate-Regeln: kein editor_script, Bild-Skript nicht in der Kette"; then
	rc=0
	# Block-Skripte nur über enqueue_block_editor_assets (Kommentare zählen nicht).
	hits=$(grep -rn "editor_script" "$THEME" --include=*.php --include=*.json | grep -vE "^[^:]+:[0-9]+:\s*(//|\*|#)" || true)
	[ -n "$hits" ] && { echo "  FEHLER: editor_script registriert:"; echo "$hits" | sed 's/^/    /'; rc=1; }
	# Das Bild-Skript ist Werkzeug, kein Prüfschritt: diese Datei ruft es nicht auf.
	pat='edi-bild''er'
	calls=$(grep -vE '^\s*#' "${BASH_SOURCE[0]}" | grep -c "$pat" || true)
	[ "$calls" -ne 0 ] && { echo "  FEHLER: gate.sh ruft das Bild-Skript auf"; rc=1; }
	[ "$rc" -eq 0 ] && echo "  ok: kein editor_script im Theme; Bild-Skript nicht in der Kette"
	finish "$rc"
fi

if begin 2b "Smoke-Test: alle Blöcke und Seiten rendern" php; then
	smoke=$(php "$ROOT/bin/checks/smoke.php" "$THEME" 2>&1); rc=$?
	echo "$smoke" | grep -E "^TOTAL|FATAL|EMPTY|notice/warning|^Galerie|^Social|^Links|^404|!! |^Seiten:" | sed 's/^/  /'
	echo "$smoke" | grep -qE "FATAL|!! " && rc=1
	gerendert=$(echo "$smoke" | sed -n 's/^Seiten: \([0-9]* von [0-9]*\).*/\1/p')
	finish "$rc" "ok (${gerendert:-?} Seiten)"
fi

if begin 2c "Startseite nach PDF, keine Doppelung, Stammdaten (NAP)" php python 2b; then
	python3 -I "$ROOT/bin/checks/startseite.py" "$ROOT" "$WORK"; finish $?
fi

if begin 2d "Referenzen: Blöcke, Icons, Assets, CSS-Klassen, mb_-Rückfall" python; then
	python3 -I "$ROOT/bin/checks/refs.py" "$ROOT"; finish $?
fi

if begin 2e "Struktur: Vorlagen, Startmuster, Hierarchie" php python; then
	python3 -I "$ROOT/bin/checks/structure.py" "$ROOT"; finish $?
fi

if begin 2f "Formulare: Contact Form 7 + Begleit-Plugin" php; then
	php "$ROOT/bin/checks/formulare.php" "$THEME" "$PLUGIN"; finish $?
fi

if begin 2g "Editierbarkeit: Felder leerbar, Wiederholfelder bearbeitbar" php; then
	out=$(php "$ROOT/bin/checks/emptiable.php" "$THEME" 2>&1); rc=$?
	if [ "$rc" -eq 0 ]; then
		echo "$out" | grep -E "^(Textfelder|  ok|Wiederholfelder|LEERBARKEIT)" | sed 's/^/  /'
	else
		# Im Fehlerfall alles zeigen - ein Filter hat hier früher den Grund verschluckt.
		echo "$out" | sed 's/^/  /' | head -40
		[ -z "$out" ] && echo "  FEHLER: emptiable.php endete mit Code $rc ohne Ausgabe"
	fi
	finish "$rc"
fi

if begin 2h "Seitenanlage: zwei Aktivierungen, Eltern, Vorlage, Inhalt, Startseite" php; then
	php "$ROOT/bin/checks/seitenanlage.php" "$THEME"; finish $?
fi

if begin 2i "Hero: Video oder Standbild, reduzierte Bewegung, Editor-Vorschau" php; then
	php "$ROOT/bin/checks/hero.php" "$THEME"; finish $?
fi

if begin 2j "Doku: Block- und Seitentabelle aktuell" php; then
	out=$(php "$ROOT/bin/docs-tabellen.php" --pruefen 2>&1); rc=$?
	echo "$out"
	[ "$rc" -ne 0 ] && [ -z "$out" ] && echo "  FEHLER: docs-tabellen.php endete mit Code $rc ohne Ausgabe"
	finish "$rc"
fi

# Regressionsschutz für den Absturz auf Hostern ohne mbstring: dasselbe
# Rendern noch einmal, alle mb_-Funktionen abgeschaltet (in PHP 8 existiert
# eine abgeschaltete Funktion nicht). WordPress ersetzt dann nur mb_substr
# und mb_strlen - genau das tut der Stub auch.
if begin 2k "Theme ohne mbstring: alle Seiten rendern mit abgeschalteten mb_-Funktionen" php; then
	mbf=$(php -r 'echo implode(",", array_filter(get_defined_functions()["internal"], fn($f) => str_starts_with($f, "mb_")));')
	mkdir -p "$WORK/ohne-mb"
	out=$(FS_OUT="$WORK/ohne-mb" php -d "disable_functions=${mbf}" "$ROOT/bin/checks/smoke.php" "$THEME" 2>&1); rc=$?
	echo "$out" | grep -E "FATAL|^Seiten:" | sed 's/^/  /'
	echo "$out" | grep -q "FATAL" && rc=1
	[ -z "$mbf" ] && echo "  (mbstring ist hier gar nicht geladen - der Lauf ist ohnehin ohne)"
	finish "$rc"
fi

# --- 3 Farben ----------------------------------------------------------------
if begin 3a "Farbkontexte: Tokens, kein Pink Tief, Hover nur aus Kontext" python; then
	python3 -I "$ROOT/bin/checks/tokens.py" "$ROOT"; finish $?
fi

if begin 3b "Kontrast rechnerisch je Fläche (WCAG AA)" python; then
	python3 -I "$ROOT/bin/checks/kontrast.py" "$ROOT"; finish $?
fi

if begin 3c "Flächenfolge je Seite (nie dreimal Vollpink)" python 2b; then
	python3 -I "$ROOT/bin/checks/flaechen.py" "$ROOT" "$WORK"; finish $?
fi

# --- 4 Browser ---------------------------------------------------------------
if begin 4 "Browser: 390/768/1024/1280/1512/1920 px, Menüs offen, Kontrast, Fokus" browser php node playwright 2b; then
	php "$ROOT/bin/checks/mkpages.php" "$THEME"
	node "$ROOT/bin/checks/browser.mjs" "$WORK"
	finish $?
fi

# --- 5 Tailwind ----------------------------------------------------------------
if begin 5 "Tailwind (nur wenn die Quelle neuer ist)"; then
	tw_src=$(find "$THEME" -maxdepth 3 \( -name 'tailwind*.css' -o -name 'tailwind.config.*' \) 2>/dev/null | head -1)
	if [ -z "$tw_src" ]; then
		echo "  entfällt – keine Tailwind-Quelle im Theme (CSS ist handgeschrieben: assets/css/main.css)"
		finish 0 "entfällt"
	elif [ "$tw_src" -nt "$THEME/assets/css/main.css" ]; then
		echo "  FEHLER: $tw_src ist neuer als main.css – Tailwind-Build ausführen"
		finish 1
	else
		echo "  ok: main.css ist aktuell"
		finish 0
	fi
fi

# --- 6 Build -----------------------------------------------------------------
if begin 6 "Build: Theme- und Plugin-ZIP" build; then
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
		finish 0 "$res"
	else
		tail -12 "$WORK/build.log" | sed 's/^/  /'
		finish 1
	fi
fi

# --- Zusammenfassung -----------------------------------------------------------
echo
printf '\033[1m%-4s %-60s %-46s %8s\033[0m\n' "Nr" "Schritt" "Ergebnis" "Dauer"
for r in "${ROWS[@]}"; do
	IFS='|' read -r no name st res dur <<<"$r"
	case "$st" in ok) col='\033[32m' ;; skip) col='\033[33m' ;; *) col='\033[31m' ;; esac
	# Breite nach Zeichen, nicht Bytes (Umlaute): printf zählt Bytes.
	pad() { python3 -c 'import sys; w=int(sys.argv[2]); s=sys.argv[1][:w]; print(s + " " * (w - len(s)))' "$1" "$2" 2>/dev/null || printf "%-${2}s" "$1"; }
	printf "%-4s %s ${col}%s\033[0m %8s\n" "$no" "$(pad "$name" 60)" "$(pad "$res" 46)" "$dur"
done
echo
if [ "${#SKIPS[@]}" -gt 0 ]; then
	printf '\033[33mÜbersprungen (%d):\033[0m\n' "${#SKIPS[@]}"
	printf '  - %s\n' "${SKIPS[@]}"
	echo
fi
if [ "$FAILED" -eq 0 ]; then
	printf '\033[32mGATE: bestanden (%d übersprungen)\033[0m\n' "${#SKIPS[@]}"
	exit 0
fi
printf '\033[31mGATE: fehlgeschlagen (%d Fehler, %d übersprungen)\033[0m\n' "$FAILED" "${#SKIPS[@]}"
exit 1
