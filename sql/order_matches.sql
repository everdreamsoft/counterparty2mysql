DROP TABLE IF EXISTS order_matches;
CREATE TABLE order_matches (
    id                 TEXT,
    tx0_index          INTEGER UNSIGNED,
    -- tx0_hash        TEXT,
    tx0_hash_id        INTEGER UNSIGNED, -- id of record in index_transactions
    -- tx0_address     TEXT,
    tx0_address_id     INTEGER UNSIGNED, -- id of record in index_addresses
    tx1_index          INTEGER UNSIGNED,
    -- tx1_hash        TEXT,
    tx1_hash_id        INTEGER UNSIGNED, -- id of record in index_transactions
    -- tx1_address     TEXT,
    tx1_address_id     INTEGER UNSIGNED, -- id of record in index_addresses
    -- forward_asset   TEXT,
    forward_asset_id   INTEGER UNSIGNED, -- id of record in assets table
    forward_quantity   BIGINT,
    -- backward_asset  TEXT,
    backward_asset_id  INTEGER UNSIGNED, -- id of record in assets table
    backward_quantity  BIGINT,
    tx0_block_index    INTEGER UNSIGNED,
    tx1_block_index    INTEGER UNSIGNED,
    block_index        INTEGER UNSIGNED,
    tx0_expiration     INTEGER UNSIGNED,
    tx1_expiration     INTEGER UNSIGNED,
    match_expire_index INTEGER UNSIGNED,
    fee_paid           BIGINT,
    status             TEXT
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX block_index       ON order_matches (block_index);
CREATE INDEX tx0_hash_id       ON order_matches (tx0_hash_id);
CREATE INDEX tx1_hash_id       ON order_matches (tx1_hash_id);
CREATE INDEX tx0_address_id    ON order_matches (tx0_address_id);
CREATE INDEX tx1_address_id    ON order_matches (tx1_address_id);
CREATE INDEX forward_asset_id  ON order_matches (forward_asset_id);
CREATE INDEX backward_asset_id ON order_matches (backward_asset_id);
CREATE INDEX tx0_index         ON order_matches (tx0_index);
CREATE INDEX tx1_index         ON order_matches (tx1_index);