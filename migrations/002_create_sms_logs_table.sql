CREATE TABLE sms_logs (
    id SERIAL PRIMARY KEY,
    telephone VARCHAR(15) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'sent',
    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    metadata JSONB
);

CREATE INDEX idx_sms_logs_telephone ON sms_logs(telephone);
CREATE INDEX idx_sms_logs_sent_at ON sms_logs(sent_at);
CREATE INDEX idx_sms_logs_status ON sms_logs(status);