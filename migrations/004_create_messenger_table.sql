CREATE TABLE messenger_messages (
    id BIGSERIAL NOT NULL,
    body TEXT NOT NULL,
    headers TEXT NOT NULL,
    queue_name VARCHAR(190) NOT NULL,
    created_at TIMESTAMP NOT NULL,
    available_at TIMESTAMP NOT NULL,
    delivered_at TIMESTAMP DEFAULT NULL,
    PRIMARY KEY(id)
);

CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name);
CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at);
CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at);