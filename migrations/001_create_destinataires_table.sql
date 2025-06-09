CREATE TABLE destinataires (
    id SERIAL PRIMARY KEY,
    insee VARCHAR(5) NOT NULL,
    telephone VARCHAR(15) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_insee ON destinataires(insee);