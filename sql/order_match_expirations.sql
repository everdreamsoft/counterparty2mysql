DROP TABLE IF EXISTS order_match_expirations;
CREATE TABLE order_match_expirations (
    order_match_id TEXT,
    -- tx0_address TEXT,
    tx0_address_id INTEGER UNSIGNED, -- id of record in index_addresses
    -- tx1_address TEXT,
    tx1_address_id INTEGER UNSIGNED, -- id of record in index_addresses
    block_index    INTEGER
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX block_index    ON order_match_expirations (block_index);
CREATE INDEX tx0_address_id ON order_match_expirations (tx0_address_id);
CREATE INDEX tx1_address_id ON order_match_expirations (tx1_address_id);
