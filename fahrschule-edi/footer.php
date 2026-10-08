<?php
/**
 * Fußbereich – wird von allen Templates eingebunden.
 *
 * @package Fahrschule_Edi
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
</main><!-- #content -->
<?php
// Globaler Footer + schwebender TikTok-Badge (zentral registrierte Blöcke).
echo fs_block( 'fahrschule/footer' );
echo fs_block( 'fahrschule/tiktok-badge' );
echo fs_block( 'fahrschule/cookie-banner' );

wp_footer();
?>
</body>
</html>
