DROP TABLE IF EXISTS messages;
CREATE TABLE messages (
    message_index INTEGER UNSIGNED, 
    block_index   INTEGER UNSIGNED,
    command       VARCHAR(50),
    category      VARCHAR(50),
    bindings      BLOB,
    timestamp     INTEGER UNSIGNED
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX message_index on messages (message_index);
CREATE INDEX block_index   on messages (block_index);
CREATE INDEX command       on messages (command);
CREATE INDEX category      on messages (category);
