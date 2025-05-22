<?php
/*********************************************************************
 * config.php - Config info / credentials
 ********************************************************************/

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Hide all but errors
error_reporting(E_ERROR);


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


if($runtype=='mainnet'){
    define("DB_HOST", "lindt.alwaysdata.net");
    define("DB_USER", "lindt_rabbit");
    define("DB_PASS", "8Kzr35BBe2q4");
    define("DB_DATA", "lindt_counterparty");
    define("CP_HOST", "https://api.counterparty.io:4000/");
    define("CP_USER", "rpc");
    define("CP_PASS", "rpc");
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
require_once(__DIR__ . '/jsonRPC/Client.php');
require_once(__DIR__ . '/counterparty-v2-api.php');
use function CounterpartyV2API;
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/profiler.php');

// Start runtime clock
$runtime = new Profiler();

?>
