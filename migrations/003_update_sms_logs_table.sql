-- Augmenter la taille de la colonne telephone pour gérer les cas d'erreur
ALTER TABLE sms_logs ALTER COLUMN telephone TYPE VARCHAR(50);