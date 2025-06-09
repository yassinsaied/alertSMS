# 📱 AlertSMS - Système d'Alertes SMS

Application Symfony pour envoyer des alertes SMS géolocalisées par code INSEE.

## 🚀 Technologies

-  **PHP 8.3** + **Symfony 6.4** + **PostgreSQL 15** + **Docker** + **Messenger**

## ⚡ Installation Rapide

```bash
# 1. Cloner et démarrer
git clone <repository-url> && cd alertSMS
docker-compose up -d --build

# 2. Setup dans le conteneur
docker exec -it alertsms_php bash
composer install
php bin/console sql-migrations:execute
```

## 🌐 Services

| Service           | URL                   | Identifiants            |
| ----------------- | --------------------- | ----------------------- |
| **Application**   | http://localhost:8000 | -                       |
| **pgAdmin 4**     | http://localhost:5050 | admin@admin.com / admin |
| **PostgreSQL 15** | localhost:5432        | postgres / postgres     |

## 📂 Gestion des Fichiers CSV

### **Structure des dossiers :**

```
data/
├── import/          # Fichiers à traiter
├── processed/       # Fichiers traités avec succès
└── error/          # Fichiers en erreur
```

### **Workflow d'import :**

```bash
# 1. Placer le fichier CSV dans data/import/
cp destinataires.csv data/import/

# 2. Lancer l'import
php bin/console app:import-csv data/import/destinataires.csv

# 3. Le fichier sera déplacé automatiquement :
#    - Succès → data/processed/destinataires_YYYYMMDD_HHMMSS.csv
#    - Erreur → data/error/destinataires_YYYYMMDD_HHMMSS.csv
```

### **Format CSV :**

```csv
insee,telephone
75001,0123456789
75001,0234567890
69001,0345678901
```

## 🔄 Workflow Complet

```bash
# 1. Import CSV
php bin/console app:import-csv data/import/destinataires.csv

# 2. Test des services SMS
php bin/console app:test-sms

# 3. Test des alertes par INSEE
php bin/console app:test-alert-sms 75001

# 4. Démarrer le système asynchrone
# Terminal 1 : Serveur
php -S localhost:8000 -t public/

# Terminal 2 : Consumer
php bin/console messenger:consume async -vv

# Terminal 3 : Envoyer alerte
curl "http://localhost:8000/alerter?insee=75001"
```

## 📡 API

### **POST/GET /alerter**

```bash
curl "http://localhost:8000/alerter?insee=75001"
```

**Réponse :**

```json
{
	"success": true,
	"message": "Alertes SMS programmees (asynchrone)",
	"insee": "75001",
	"destinataires_count": 2,
	"messages_dispatched": 2
}
```

## 🔧 Commandes Principales

```bash
# Import et gestion des fichiers
php bin/console app:import-csv data/import/fichier.csv

# Tests
php bin/console app:test-sms                    # Test service SMS général
php bin/console app:test-alert-sms 75001       # Test alerte par code INSEE
php bin/console app:add-destinataire           # Ajout manuel

# Production
php bin/console messenger:consume async -vv

# Debug
php bin/console dbal:run-sql "SELECT * FROM destinataires;"
php bin/console debug:router | grep alerter
```

## 🏗️ Architecture

```
CSV Import → Destinataires → Endpoint /alerter → Messenger Queue → SMS Service → Logs
```

**4 parties :**

1. **Import CSV** avec déplacement des fichiers
2. **Service SMS** (simulation) + commandes de test
3. **Endpoint API** pour déclencher les alertes
4. **Traitement asynchrone** via Messenger

## 📋 Stack Technique Détaillée

-  **Runtime** : PHP 8.3
-  **Framework** : Symfony 6.4
-  **Base de données** : PostgreSQL 15
-  **Queue** : Symfony Messenger (avec transport Doctrine)
-  **Container** : Docker + Docker Compose
-  **Admin DB** : pgAdmin 4
-  **Logging** : Monolog

> **Note** : Variables d'environnement présentes dans `.env` uniquement pour ce test. En production, elles ne doivent pas être commitées dans le repository.

---

**Prêt à l'emploi en 2 minutes !** 🚀
