DROP TABLE IF EXISTS rps_matches;
CREATE TABLE rps_matches (
    id                      TEXT,
    tx0_index               INTEGER UNSIGNED,
    -- tx0_hash             TEXT,
    tx0_hash_id             INTEGER UNSIGNED, -- id of record in index_transactions
    -- tx0_address          TEXT,
    tx0_address_id          INTEGER UNSIGNED, -- id of record in index_addresses
    tx1_index               INTEGER UNSIGNED,
    -- tx1_hash             TEXT,
    tx1_hash_id             INTEGER UNSIGNED, -- id of record in index_transactions
    -- tx1_address          TEXT,
    tx1_address_id          INTEGER UNSIGNED, -- id of record in index_addresses
    -- tx0_move_random_hash TEXT,
    tx0_move_random_hash_id INTEGER UNSIGNED, -- id of record in index_transactions
    -- tx1_move_random_hash TEXT,
    tx1_move_random_hash_id INTEGER UNSIGNED, -- id of record in index_transactions
    wager                   INTEGER UNSIGNED,
    possible_moves          INTEGER UNSIGNED,
    tx0_block_index         INTEGER UNSIGNED,
    tx1_block_index         INTEGER UNSIGNED,
    block_index             INTEGER UNSIGNED,
    tx0_expiration          INTEGER UNSIGNED,
    tx1_expiration          INTEGER UNSIGNED,
    match_expire_index      INTEGER UNSIGNED,
    status                  TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX block_index             ON rps_matches (block_index);
CREATE INDEX tx0_hash_id             ON rps_matches (tx0_hash_id);
CREATE INDEX tx1_hash_id             ON rps_matches (tx1_hash_id);
CREATE INDEX tx0_address_id          ON rps_matches (tx0_address_id);
CREATE INDEX tx1_address_id          ON rps_matches (tx1_address_id);
CREATE INDEX tx0_move_random_hash_id ON rps_matches (tx0_move_random_hash_id);
CREATE INDEX tx1_move_random_hash_id ON rps_matches (tx1_move_random_hash_id);
CREATE INDEX tx0_index               ON rps_matches (tx0_index);
CREATE INDEX tx1_index               ON rps_matches (tx1_index);

