#!/usr/bin/env php
<?php
/*********************************************************************
 * counterparty2mysql.php 
 * 
 * Script to handle parsing counterparty data into mysql database
 * 
 * Command line arguments :
 * --testnet    Load data from testnet
 * --regtest    Load data from regtest
 * --block=#    Load data for given block
 * --rollback=# Rollback data to a given block
 * --single     Load single block
 * --silent     Fail silently on insert errors
 ********************************************************************/

// Hide all but errors
error_reporting(E_ERROR);

// Parse in the command line args and set some flags based on them
$args     = getopt("", array("testnet::", "regtest::", "block::", "rollback::", "single::","silent::", "verbose::"));
$testnet  = (isset($args['testnet'])) ? true : false;
$regtest  = (isset($args['regtest'])) ? true : false;
$single   = (isset($args['single'])) ? true : false;  
$silent   = (isset($args['silent'])) ? true : false; // Flag to indicate if we should silently fail on insert errors
$runtype  = ($regtest) ? 'regtest' : (($testnet) ? 'testnet' : 'mainnet');
$rollback = (is_numeric($args['rollback'])) ? intval($args['rollback']) : false;
$block    = (is_numeric($args['block'])) ? intval($args['block']) : false;

// Load config (only after runtype is defined)
require_once(__DIR__ . '/includes/config.php');

// Initialize the database and counterparty API connections
initDB(DB_HOST, DB_USER, DB_PASS, DB_DATA, true);
initCP(CP_HOST, CP_USER, CP_PASS, true);

// Create a lock file, and bail if we detect an instance is already running
createLockFile();

// Handle rollbacks
if($rollback){
    $block_index = $mysqli->real_escape_string($rollback);
    $tables = [
        'bets',
        'bet_expirations', 
        'bet_match_expirations',
        'bet_match_resolutions',
        'bet_matches',
        'blocks',
        'broadcasts',
        'btcpays',
        'burns',
        'cancels',
        'contracts',
        'credits',
        'debits',
        'destructions',
        'dispensers',
        'dispenses',
        'dividends',
        'executions',
        'fairminters',
        'fairmints',
        'issuances',
        'index_tx',
        'messages',
        'orders',
        'order_expirations',
        'order_match_expirations',
        'order_matches',
        'rps',
        'rps_expirations',
        'rps_match_expirations',
        'rps_matches',
        'rpsresolves',
        'sends',
        'sweeps',
        'transactions'
    ];
    foreach($tables as $table){
        $results = $mysqli->query("DELETE FROM {$table} WHERE block_index>{$block_index}");
        if(!$results)
            byeLog("Error while trying to rollback {$table} table to block {$block_index}");
    }
    byeLog("Rollback to block {$block_index} complete.");
}

// If no block given, load last block from state file, or use first block with CP tx
if(!$block){
    $last  = file_get_contents(LASTFILE);
    $first = ($regtest) ? 1 : (($testnet) ? 310000 : 278270);
    $block = (isset($last) && $last>=$first) ? (intval($last) + 1) : $first;
}

// Flag to indicate if we should update market/asset prices as we parse each block
// Set this to false if you want a faster parse (price updates take a lil while)
// NOTE: If this is set to false, be sure to run the following scripts after your done with your parse to update asset and market prices
// ./misc/update_asset_prices.php
// ./misc/update_market_info.php --update
$updatePrices = true;

// Flag to indicate if we should update balances as we parse each block
// Set this to false if you want a faster parse 
// NOTE: If this is set to false, be sure to run the following scripts after your done with your parse to update all address balances since block_index
// ./misc/fix_address_balances.php --block=block_index
$updateBalances = true;

// Get the current block index from status info
$current = $counterparty->status['last_block']['block_index'];

// Define array of fields that contain assets, addresses, transactions, and contracts
$fields_asset       = array('asset', 'backward_asset', 'dividend_asset', 'forward_asset', 'get_asset', 'give_asset','asset_parent');
$fields_address     = array('address', 'bet_hash', 'destination', 'feed_address', 'issuer', 'source', 'oracle_address', 'tx0_address', 'tx1_address', 'origin', 'last_status_tx_source', 'destination_address', 'source_address', 'utxo_address');
$fields_transaction = array('event', 'move_random_hash', 'offer_hash', 'order_hash', 'rps_hash', 'tx_hash', 'tx0_hash', 'tx0_move_random_hash', 'tx1_hash', 'tx1_move_random_hash', 'dispenser_tx_hash', 'last_status_tx_hash', 'dispenser_tx_hash', 'block_hash', 'fairminter_tx_hash', 'utxo');
$fields_contract    = array('contract_id');

// Loop through the blocks until we are current
while($block <= $current){
    $timer = new Profiler();
    print "processing block {$block}...";

    // create block record
    $blockTime = createBlock($block);
    echo ("blocktime $blockTime");

    // Define array hold asset/address/tranaction id mappings for this block
    // We want to reset these every block since we use the assets list querying address balances
    $assets       = array(); // array of asset id mappings
    $addresses    = array(); // array of address id mappings
    $transactions = array(); // array of transaction id mappings
    $contracts    = array(); // array of contract id mappings

    // Get list of messages (updates to counterparty tables)
    $messages = $counterparty->execute('get_messages', array('block_index' => $block));

    // Filter out abusive transactions (optional)
    // $data = array();
    // foreach($messages as $message){
    //     $msg      = (object) $message;
    //     $table    = $msg->category;
    //     $bindings = json_decode($msg->bindings);
    //     if(in_array($table, array('credits','debits','issuances','sends')) && substr($bindings->asset,0,1)=='A')
    //         continue;
    //     array_push($data, $msg);
    // }
    // $messages = $data;

    // Loop through messages and create assets, addresses, transactions and setup id mappings
    foreach($messages as $message){
        $msg = (object) $message;
        $obj = json_decode($msg->bindings);
        foreach($obj as $field => $value){
            // assets
            foreach($fields_asset as $name)
                if($field==$name && !isset($assets[$value]))
                    $assets[$value] = createAsset($value, $block);
            // addresses
            foreach($fields_address as $name)
                if($field==$name && !isset($addresses[$value]))
                    $addresses[$value] = createAddress($value);
            // transactions
            foreach($fields_transaction as $name)
                if($field==$name && !isset($transactions[$value]))
                    $transactions[$value] = createTransaction($value);
            // contracts
            foreach($fields_contract as $name)
                if($field==$name && !isset($contracts[$value]))
                    $contracts[$value] = createContract($value);
        }
        // Create record in tx_index (so we can map tx_index to tx_hash and table with data)
        if(isset($obj->tx_index) && isset($obj->block_index) && isset($transactions[$obj->tx_hash]) && $msg->category!='transactions' && $msg->category!='transaction_outputs')
            createTxIndex($obj->tx_index, $obj->block_index, $msg->category, $transactions[$obj->tx_hash]);
        // Create record in the messages table (so we can review the CP messages as needed)
        createMessage($message);
    }



    // Loop through addresses and update any asset balances
    // Doing this first ensures that address balances are correct immediately
    if($updateBalances){
        foreach($addresses as $address => $address_id)
            updateAddressBalance($address, array_keys($assets));
    }

    // Loop through the messages and create/update the counterparty tables
    foreach($messages as $message){
        $msg      = (object) $message;
        $table    = $msg->category;
        $bindings = json_decode($msg->bindings);
        $command  = $msg->command;

        // v10.0.0 - Ignore certain messages for now as they conflict with our already existing tables and bloats database by not indexing addresses/hashes via id
        if(in_array($table,array('assets', 'blocks', 'transaction_outputs')))
            continue;

        // Build out array of fields and values
        $fields = array();
        $values = array();
        $fldmap = array();
        foreach($bindings as $field => $value){
            $ignore = false;
            // swap asset name for id
            foreach($fields_asset as $name)
                if($field==$name){
                    $field = $name . '_id';
                    $value = $assets[$value];
                }
            // swap address for id
            foreach($fields_address as $name)
                if($field==$name){
                    $field = $name . '_id';
                    $value = $addresses[$value];
                }
            // swap transaction for id
            foreach($fields_transaction as $name)
                if($field==$name){
                    $field = $name . '_id';
                    $value = $transactions[$value];
                }
            // swap contract for id
            foreach($fields_contract as $name)
                if($field==$name)
                    $value = $contracts[$value];
            // Force numeric values on some broadcast values
            if($table=='broadcasts'){
                if(in_array($field,array('locked','fee_fraction_int')))
                    $value = intval($value);
                if($field=='value' && $value=='')
                    $value = 0;
                // Replace 4-byte UTF-8 characters (fixes issue with breaking SQL queries) 
                if($field=='text')
                    $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value);
            }
            // Replace 4-byte UTF-8 characters (fixes issue with breaking SQL queries) 
            if($field=='description'){
                $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value);
                // Truncate to first 10K characters
                $value = substr($value,0,10000); 
            }
            // Translate some field names where bindings field names and table field names differ
            if($table=='credits' && $field=='action')
                $field='calling_function';
            // Unset certain fields with no value set (fixes mysql complaints)
            if($table=='issuances'){
                if(in_array($field, array('locked','transfer','divisible','callable')) && $value=='')
                    $ignore = true;
                // Handle issues with unpacking data where all values are empty
                if(in_array($field, array('call_date','call_price','quantity')) && $value=='')
                    $value = 0;
                // Handle fair_minting as false, (real_escape_string of false gives empty string which is not compatible with column type in DB)
                if (in_array($field, array('fair_minting')) && $value == false)
                    $value = 0;                
            }
            // Rock / Paper / Sciscors
            if($table=='rps'){
                // Force move_random_hash_id to numeric value
                if($field=='move_random_hash_id' && !isset($value))
                    $value = intval($value);
                // Ignore the 'calling_function'
                if($field=='calling_function')
                    $ignore = true;
            }
            // Force numeric values on utxo fields
            if($table=='credits' || $table=='debits'){
                if(($field=='utxo_id' || $field=='utxo_address_id') && (!isset($value) || is_null($value)))
                    $value = intval($value);
            }
            if($table=='sends'){
                if($field=='quantity')
                    $value = intval($value);
                if($field=='msg_index')
                    $ignore = true;
            }
            // Handle btc_amount/fee null cases, (real_escape_string of false/null gives empty string which is not compatible with column type in DB)
            if($table == 'transactions'){
                if(($field == 'btc_amount' || $field == 'fee') && !isset($value))
                    $value = intval($value);
            }
            if($table=='dispensers'){
                if($field=='prev_status')
                    $ignore = true;
                // Force null value to integer value
                if($field=='last_status_tx_hash_id' && $value==null)
                    $value = 0;
                // v10.0.0 - Ignore certain fields for now
                if(in_array($field,array('rowid','dispense_count')))
                    $ignore=true;
            }
            if($table=='dispenser_refills'){
                if(in_array($field, array('dispenser_quantity','status')))
                    $ignore = true;
            }
            // Force `reset` to boolean value
            if($field=='reset'){
                // Ignore field if this is a destruction
                if($table=='destructions'){
                    $ignore = true;
                } else {
                    $value = intval($value);
                }
            }
            // EVM fields
            if($field=='gasprice')
                $field = 'gas_price';
            if($field=='startgas')
                $field = 'gas_start';
            if($field=='payload')
                $field = 'data';
            // Escape key/value field names to prevent sql errors 
            if($field=='key')
                $field = '`key`';            
            if($field=='value')
                $field = '`value`';
            // Handle ignoring certain items in the bindings that cause issues
            if(in_array($field,array('asset_longname')) || $ignore)
                continue;
            // Make value safe for use in SQL queries
            $value = $mysqli->real_escape_string($value);
            // Add final field and value values to arrays
            array_push($fields, $field);
            array_push($values, $value);
            $fldmap[$field] = $value;
        }

        // Change command to 'replace'
        if($msg->category=='replace')
            $command = 'replace';

        // Handle creating/updating records in the 'addresses' table
        if($command=='replace'){
            // Extract data to usable variable name
            foreach($fields as $ndx => $field)
                $$field = $values[$ndx];
            // Check if this record already exists
            $sql = "SELECT * FROM addresses WHERE address_id='{$address_id}'";
            $results = $mysqli->query($sql);
            if($results){
                // Only create the record if it does not already exist
                if($results->num_rows==0){
                    $sql2 = "INSERT INTO addresses (" . implode(",", $fields)  . ") values ('" . implode("', '", $values) . "')";
                } else {
                    $sql2 = "UPDATE addresses SET options='{$options}', block_index='{$block_index}' WHERE address_id='{$address_id}'";
                }
                $results2 = $mysqli->query($sql2);
                if(!$results2)
                    byeLog('Error while trying to create or record in addresses table : ' . $sql2);
            } else {
                byeLog('Error while trying to check if record already exists in addresses table : ' . $sql);
            }
        }

        // Handle 'insert' commands
        if($command=='insert'){
            // Check if this record already exists
            $sql = "SELECT * FROM {$table} WHERE";

            $sqlUpdate = '';
            foreach($fields as $index => $field){
                $sql .= " {$field}='{$values[$index]}' AND";

                //building potential update statement
                $sqlUpdate .= " {$field}='{$values[$index]}' AND";
            }

            $sql = rtrim($sql, " AND");
            $sqlUpdate = rtrim($sqlUpdate, " AND");

            // print "{$sql}\n";
            $results = $mysqli->query($sql);
            if($results){
                //on duplicate key statement will update the row if exists already
                if($results->num_rows==0){
                    $sql = "INSERT INTO {$table} (" . implode(",", $fields)  . ") values ('" . implode("', '", $values) . "') ON DUPLICATE KEY UPDATE $sqlUpdate";
                    $results = $mysqli->query($sql);
                    if(!$results && !$silent)
                        byeLog('Error while trying to create record in ' . $table . ' : ' . $sql);
                }
            } else {
                byeLog('Error while trying to check if record already exists in ' . $table . ' : ' . $sql);
            }
        }

        // Handle 'update' commands
        if($command=='update'){
            $sql   = "UPDATE {$table} SET";
            $where = "";
            foreach($fields as $index => $field){
                // Update bets and orders records using tx_hash
                if(in_array($table,array('orders','bets','fairminters')) && $field=='tx_hash_id'){
                    if($where!="")
                        $where .= " AND ";
                    $where .= " tx_hash_id='{$values[$index]}'";
                // Update *_matches tables using id field
                } else if(in_array($table,array('order_matches','bet_matches','rps_matches')) && 
                          in_array($field,array('order_match_id','bet_match_id','rps_match_id', 'id'))){
                    $where = " id='{$values[$index]}'";
                // Update rps table using tx_hash or tx_index
                } else if($table=='rps' && in_array($field,array('tx_hash_id','tx_index'))){
                    $where .= " {$field}='{$values[$index]}'";
                // Update nonces table using address_id
                } else if($table=='nonces' && $field=='address_id'){
                    $where .= " {$field}='{$values[$index]}'";
                // Set correct whereSQL for dispenser updates
                } else if($table=='dispensers' && in_array($field, array('block_index','status','asset_id', 'tx_index','action'))){
                    // Skip updates on certain fields
                    if(in_array($field, array('block_index','asset_id','action')))
                        continue;
                    // Only allow status updates to status=11 (Closing) andstatus=10 (Closed) since status can only go from Open to Closed in updates (otherwise we could open up previously closed dispensers...yikes)
                    if($field=='status' && ($values[$index]==10||$values[$index]==11))
                        $sql   .= " status='{$values[$index]}',";
                    // Update dispensers using tx_index if we have it, otherwise default to using source and asset to identify dispenser
                    if($where==""){
                        if(in_array('tx_index',array_values($fields))){
                            $where = " tx_index='{$fldmap['tx_index']}'";
                        } else {
                            $where = " source_id='{$fldmap['source_id']}' AND asset_id='{$fldmap['asset_id']}'";
                        }
                    }
                // Skup updating the id field unnecessarily when updating an order match
                } else if($table=='order_matches' && $field=='id'){
                    continue;
                } else {
                    $sql .= " {$field}='{$values[$index]}',";
                }
            }
            // Only proceed if we have a valid where criteria
            if($where!=""){
                $sql = rtrim($sql,',') . " WHERE " .  $where;
            } else {
                byeLog('Error - no WHERE criteria found');
            }
            // print "{$sql}\n";
            $results = $mysqli->query($sql);
            if(!$results)
                byeLog('Error while trying to update record in ' . $table . ' : ' . $sql);
        }

    }

    // Loop through assets and update BTC & XCP price 
    foreach($assets as $asset =>$id)
        if($updatePrices)
            updateAssetPrice($asset);

    // array of markets
    $markets = array(); 

    // Loop through messages and detect any DEX market changes
    foreach($messages as $message){
        $msg = (object) $message;
        $obj = json_decode($msg->bindings);
        $market = false;
        if($msg->category='orders'){
            $sql = "SELECT
                        a1.asset as asset1,
                        a2.asset as asset2
                    FROM
                        orders o,
                        assets a1,
                        assets a2,
                        index_transactions t
                    WHERE
                        t.id=o.tx_hash_id AND
                        a1.id=o.give_asset_id AND
                        a2.id=o.get_asset_id AND
                        t.hash='{$obj->tx_hash}'";
            $results = $mysqli->query($sql);
            if($results){
                if($results->num_rows){
                    $row = (object) $results->fetch_assoc();
                    if(!$markets[$row->asset2 . '|' . $row->asset1])
                        $markets[$row->asset1 . '|' . $row->asset2] = 1;
                }
            }
        }
    }
    // If we have any market changes, update the markets
    if(count($markets) && $updatePrices){
        $block_24hr = get24HourBlockIndex();
        createUpdateMarkets($markets);
    }

    // Get list of transactions from the transactions table (used to track BTC paid and miners fee)
    // $transactions = $counterparty->execute('get_transactions', array('filters' => array("field" => "block_index", "op" => "==", "value" => $block)));
    // foreach($transactions as $transaction)
    //     createTransactionHistory($transaction);




        // Create a DateTime object from the blocktimestamp
$blockDatetime = new DateTime();
$blockDatetime->setTimestamp($blockTime);

// Create a DateTime object for now
$now = new DateTime();

// Calculate the difference between now and the block date
$interval = $blockDatetime->diff($now);

// Display the block date and the difference in a human-readable way
if ($interval->y > 0) {
    $dateString = $blockDatetime->format('Y-m-d H:i:s') . " (" . $interval->y . " years ago)";
} elseif ($interval->m > 0) {
    $dateString = $blockDatetime->format('Y-m-d H:i:s') . " (" . $interval->m . " months ago)";
} elseif ($interval->d > 0) {
    $dateString = $blockDatetime->format('Y-m-d H:i:s') . " (" . $interval->d . " days ago)";
} elseif ($interval->h > 0) {
    $dateString = $blockDatetime->format('Y-m-d H:i:s') . " (" . $interval->h . " hours ago)";
} elseif ($interval->i > 0) {
    $dateString = $blockDatetime->format('Y-m-d H:i:s') . " (" . $interval->i . " minutes ago)";
} else {
    $dateString = $blockDatetime->format('Y-m-d H:i:s') . " (" . $interval->s . " seconds ago)";
}


    // Report time to process block
    $time = $timer->finish();
    print " Done [{$time}ms] $dateString \n";

    // Bail out if user only wants to process one block
    if($single){
        print "detected single block... bailing out\n";
        break;
    } else {
        // Save block# to state file (so we can resume from this block next run)
        file_put_contents(LASTFILE, $block);
    }

    // Increase block before next loop
    $block++;
}    

// Remove the lockfile now that we are done running
removeLockFile();

print "Total Execution time: " . $runtime->finish() ." seconds\n";


?>
