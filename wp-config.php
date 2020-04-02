<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'Quarantine' );

/** MySQL database username */
define( 'DB_USER', 'root' );

/** MySQL database password */
define( 'DB_PASSWORD', 'root' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'LD,Dr](~TV#,QBsptz/1RBWQo?Pa4^)$w$OX<T9O;E#rR}{}X|53LF(P]mCmU0~|' );
define( 'SECURE_AUTH_KEY',  '9Ulne%PUA?,5dp:^X=WXn{c[cV-{JwRWtju2.Ar%s!|,#m~7|gN;paD|B4#|o:(x' );
define( 'LOGGED_IN_KEY',    '*7zsShSf,e;,I}S|hW$Ez|C)USu|D>.~frVs(Leju7#9h{[c4]Q-rnN{6,n)*1$m' );
define( 'NONCE_KEY',        'NBP]T{vxQv@q~]H08coM,5Lk!QB_b<gynZ8`P1*fdR%D,OUFTz#C<j%C)FRG rwc' );
define( 'AUTH_SALT',        'j{e^fE6+ea`R*B5:HrxIO{,T`t5@G&tm|+A>?xD:;}a*kg|[QZ/NA.&Q&Pug:hvc' );
define( 'SECURE_AUTH_SALT', ']xyZ&Azp0>5YHE:u*BRNKjzs^Q2[]>%+4Wj T61 IS;_<jMV|,*GX-qaz54%N8J>' );
define( 'LOGGED_IN_SALT',   'nDyD&!sJiIreAK?J:H_NJ=R!7)KR>^4/J,flHhS>{a1g84Vp8!CFU|=b,mp5fcdR' );
define( 'NONCE_SALT',       'N~L}rCh)~Q>h<,ZZM87@f4Eaw49^9vToy{C2WU]cPi),|N: ~Gb3c)HEbzoMHf n' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
