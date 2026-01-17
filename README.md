# Laravel E-commerce Backend

Projekt Laravel w najnowszej wersji działający w środowisku Docker.

## Wymagania

- Docker Desktop (lub Docker Engine + Docker Compose)
- Docker Hub Account (wymagane uwierzytelnienie dla pobierania obrazów)

## Instalacja

### 1. Uwierzytelnienie Docker Hub

Przed instalacją upewnij się, że jesteś zalogowany do Docker Hub:

```bash
docker login
```

### 2. Instalacja Laravel przez Composer

#### Opcja A: Instalacja przez Docker (Zalecane)

Jeśli masz dostęp do Docker Hub, możesz zainstalować Laravel bezpośrednio:

```bash
docker run --rm -v ${PWD}:/app -w /app composer:latest create-project laravel/laravel . --prefer-dist --no-interaction
```

#### Opcja B: Instalacja zależności w istniejącym kontenerze

Jeśli masz już utworzoną strukturę (composer.json), możesz zainstalować zależności:

```bash
docker-compose up -d --build
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
```

### 3. Konfiguracja środowiska

Skopiuj plik `.env.example` do `.env` (jeśli istnieje) lub utwórz plik `.env`:

```bash
cp .env.example .env
```

Następnie wygeneruj klucz aplikacji:

```bash
docker-compose exec app php artisan key:generate
```

### 4. Uruchomienie aplikacji

```bash
docker-compose up -d
```

Aplikacja będzie dostępna pod adresem: http://localhost

## Struktura projektu

- `app/` - Logika aplikacji
- `config/` - Pliki konfiguracyjne
- `database/` - Migracje i seedery
- `public/` - Pliki publiczne
- `resources/` - Widoki i zasoby
- `routes/` - Definicje tras
- `storage/` - Pliki tymczasowe
- `tests/` - Testy

## Usługi Docker

- **app** - Kontener PHP-FPM (port 9000)
- **webserver** - Kontener Nginx (port 80)
- **db** - Kontener MySQL 8.0 (port 3306)

## Dostęp do bazy danych

```
Host: localhost
Port: 3306
Database: laravel
Username: laravel
Password: secret
Root Password: root
```

## Przydatne komendy

```bash
# Uruchomienie kontenerów
docker-compose up -d

# Zatrzymanie kontenerów
docker-compose down

# Wyświetlenie logów
docker-compose logs -f

# Wykonanie komendy Artisan
docker-compose exec app php artisan [komenda]

# Wykonanie komendy Composer
docker-compose exec app composer [komenda]

# Dostęp do konsoli kontenera
docker-compose exec app bash

# Przebudowanie kontenerów
docker-compose up -d --build
```

## Rozwiązywanie problemów

### Problem z uwierzytelnianiem Docker Hub

Jeśli otrzymujesz błąd "authentication required", zaloguj się do Docker Hub:

```bash
docker login
```

### Problem z uprawnieniami

Jeśli masz problemy z uprawnieniami do katalogów storage i cache:

```bash
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

## Wersje

- Laravel: Najnowsza wersja (11.x/12.x)
- PHP: 8.3
- MySQL: 8.0
- Nginx: Alpine
