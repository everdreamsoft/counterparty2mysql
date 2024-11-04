<?php
/*********************************************************************
 * config.php - Config info / credentials
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

/* Log, lock, and state file config */
define("LOCKFILE", '/var/tmp/counterparty2mysql-cp20-' . $runtype . '.lock');
define("LASTFILE", '/var/tmp/counterparty2mysql-cp20-' . $runtype . '.last-block');
define("ERRORLOG", '/var/tmp/counterparty2mysql-cp20-' . $runtype . '.errors');

/* Mainnet config */
/*
if($runtype=='mainnet'){
    define("DB_HOST", "localhost");
    define("DB_USER", "root");
    define("DB_PASS", "");
    define("DB_DATA", "Hazama");
    define("CP_HOST", "http://localhost:24000/api/");
    define("CP_USER", "rpc");
    define("CP_PASS", "rpc");
}
*/


if($runtype=='monaparty'){
    define("DB_HOST", "127.0.0.1");
    define("DB_USER", "root");
    define("DB_PASS", "");
    define("DB_DATA", "Monaparty");
    define("CP_HOST", "https://counterparty.api.monaparty.me/");
    define("CP_USER", "rpc");
    define("CP_PASS", "");
}

if($runtype=='mainnet'){
    define("DB_HOST", "127.0.0.1");
    define("DB_USER", "root");
    define("DB_PASS", "");
    define("DB_DATA", "Counterparty");
    define("CP_HOST", "http://public.coindaddy.io:4000/api/");
    define("CP_USER", "rpc");
    define("CP_PASS", "1234");
}

/* Testnet config */
if($runtype=='testnet'){
    define("DB_HOST", "localhost");
    define("DB_USER", "root");
    define("DB_PASS", "");
    define("DB_DATA", "Counterparty_Testnet");
    define("CP_HOST", "http://eve.everdreamsoft.com:4000/api/");
    define("CP_USER", "rpc");
    define("CP_PASS", "rpcGUISEPPEXCP");
}

/* Regtest config */
if($runtype=='regtest'){
    define("DB_HOST", "localhost");
    define("DB_USER", "root");
    define("DB_PASS", "");
    define("DB_DATA", "Counterparty_Regtest");
    define("CP_HOST", "https://eve.everdreamsoft.com/_api");
    define("CP_USER", "counterparty_username");
    define("CP_PASS", "counterparty_password");
}

// Require various libraries
require_once('jsonRPC/Client.php');
require_once('functions.php');
require_once('profiler.php');

// Start runtime clock
$runtime = new Profiler();

?>
