<?php
/**
 * Plugin Name: edi-integrationen
 * Description: Anbindungen an externe Systeme für das Fahrschule-edi-Theme: Odoo-Lead-Webhook (Formulare) und Matomo-Statistik. Bewusst getrennt vom Theme – Funktionalität soll einen Theme-Wechsel überleben.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Text Domain: edi-integrationen
 * License: GPL-2.0-or-later
 *
 * WARUM EIN EIGENES PLUGIN
 * WordPress trennt Zuständigkeiten entlang einer einfachen Linie: Aussehen
 * gehört ins Theme, Anbindungen an fremde Systeme ins Plugin. Ein
 * CRM-Webhook und eine Analytics-Einbindung fallen klar auf die zweite
 * Seite. Lägen sie im Theme, würde ein späterer Theme-Wechsel die
 * Lead-Erfassung und die Statistik-Erfassung stillschweigend abschalten –
 * und zwar genau dann, wenn niemand damit rechnet.
 *
 * Das Theme funktioniert vollständig ohne dieses Plugin:
 *   • inc/forms.php prüft function_exists( 'fahrschule_odoo_send_lead' )
 *   • Matomo bindet ausschließlich dieses Plugin selbst ein (kein Hook im
 *     Theme nötig, siehe inc/matomo.php) – ohne Plugin wird schlicht nichts
 *     getrackt.
 * Fehlt das Plugin, versenden die Formulare weiterhin per E-Mail. Es geht
 * nichts kaputt, es fehlt nur die jeweilige Anbindung.
 *
 * @package Edi_Integrationen
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'EDI_INTEGRATIONEN_VERSION', '1.0.0' );

require_once __DIR__ . '/inc/odoo.php';
require_once __DIR__ . '/inc/matomo.php';
require_once __DIR__ . '/inc/settings.php';
