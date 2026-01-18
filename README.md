# Laravel E-commerce Backend

Projekt backendowy sklepu internetowego z odzieżą używaną, zbudowany na frameworku Laravel 11. Aplikacja obsługuje zarządzanie produktami, koszykiem zakupowym, zamówieniami, płatnościami przez Stripe oraz systemem rabatowym.

## 🚀 Funkcjonalności

- **Zarządzanie produktami** - CRUD dla produktów z kategoriami, stanem magazynowym i zdjęciami
- **Koszyk zakupowy** - Dodawanie, edycja i usuwanie produktów z koszyka
- **System zamówień** - Tworzenie zamówień z automatyczną numeracją i śledzeniem statusu
- **Płatności Stripe** - Integracja z Laravel Cashier i Stripe Payment Intents
- **System rabatowy** - Kody rabatowe z walidacją (procentowe i stałe)
- **Zarządzanie stanem magazynowym** - Rezerwacja, zwolnienie i blokady pesymistyczne
- **Autentykacja** - Laravel Breeze API z logowaniem przez Google (Socialite)
- **Webhooks Stripe** - Obsługa zdarzeń płatności (succeeded, failed, canceled, refunded)
- **Generowanie faktur PDF** - Asynchroniczne generowanie faktur przez kolejki (Dompdf)
- **Events i Listeners** - Obsługa zdarzeń zmiany statusu zamówień
- **REST API** - Pełne API z zasobami (API Resources) dla frontendu

## 🛠 Używane technologie

### Backend
- **Laravel 11.x** - Framework PHP
- **PHP 8.3** - Język programowania
- **MySQL 8.0** - Baza danych relacyjna
- **Laravel Sanctum** - Autentykacja API (tokeny)
- **Laravel Breeze** - Scaffolding autentykacji API
- **Laravel Cashier** - Integracja z Stripe dla płatności
- **Laravel Socialite** - Logowanie przez Google OAuth
- **Dompdf** - Generowanie faktur PDF

### Narzędzia i biblioteki
- **Docker & Docker Compose** - Konteneryzacja aplikacji
- **Nginx** - Serwer WWW (Alpine)
- **PHP-FPM** - Procesor PHP
- **Composer** - Menadżer zależności PHP
- **Laravel Pint** - Narzędzie do formatowania kodu

### Testy
- **PHPUnit 11.x** - Framework testowy
- **Mockery** - Mockowanie zewnętrznych API (Stripe)
- **RefreshDatabase** - Testy z transakcjami bazy danych

### DevOps
- **GitHub Actions** - CI/CD pipeline
- **Git** - Kontrola wersji

## 📋 Wymagania systemowe

- Docker Desktop (lub Docker Engine + Docker Compose)
- Docker Hub Account (wymagane uwierzytelnienie dla pobierania obrazów)
- Minimum 4GB RAM
- 10GB wolnego miejsca na dysku

## 🚀 Instalacja

### 1. Sklonuj repozytorium

```bash
git clone https://github.com/gmaxsoft/Laravel_Ecomerce_Backend.git
cd Laravel_Ecomerce_Backend
```

### 2. Uwierzytelnienie Docker Hub

Przed instalacją upewnij się, że jesteś zalogowany do Docker Hub:

```bash
docker login
```

### 3. Instalacja zależności

```bash
docker-compose up -d --build
docker-compose exec app composer install
```

### 4. Konfiguracja środowiska

Skopiuj plik `.env.example` do `.env`:

```bash
cp .env.example .env
```

Następnie wygeneruj klucz aplikacji:

```bash
docker-compose exec app php artisan key:generate
```

### 5. Konfiguracja bazy danych

W pliku `.env` ustaw:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

### 6. Uruchomienie migracji

```bash
docker-compose exec app php artisan migrate
```

### 7. Konfiguracja Stripe (opcjonalnie)

W pliku `.env` dodaj klucze Stripe:

```env
STRIPE_KEY=pk_test_your_publishable_key
STRIPE_SECRET=sk_test_your_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### 8. Konfiguracja Google OAuth (opcjonalnie)

W pliku `.env` dodaj klucze Google:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://localhost/api/auth/google/callback
```

### 9. Uruchomienie aplikacji

```bash
docker-compose up -d
```

Aplikacja będzie dostępna pod adresem: **http://localhost**

## 📁 Struktura projektu

```
Laravel_Ecomerce_Backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # Kontrolery API (Product, Cart, Order, Coupon, Webhook)
│   │   │   └── Auth/             # Kontrolery autentykacji (Login, Register, Social)
│   │   ├── Middleware/           # Middleware aplikacji
│   │   ├── Requests/             # Form Request walidacja
│   │   └── Resources/            # API Resources (ProductResource, OrderResource, etc.)
│   ├── Events/                   # Eventy (OrderCreated, OrderStatusChanged)
│   ├── Listeners/                # Listenery (SendOrderStatusNotification)
│   ├── Jobs/                     # Kolejki (GenerateInvoicePdf)
│   ├── Services/                 # Serwisy biznesowe (InventoryService)
│   ├── Models/                   # Modele Eloquent (User, Product, Order, Cart, Coupon, Payment)
│   └── Providers/                # Service Providers
├── config/                       # Pliki konfiguracyjne (cashier, services, sanctum)
├── database/
│   ├── factories/                # Factory dla testów (UserFactory, ProductFactory, etc.)
│   ├── migrations/               # Migracje bazy danych
│   └── seeders/                  # Seedery danych
├── routes/
│   ├── api.php                   # Trasy API REST
│   ├── auth.php                  # Trasy autentykacji
│   └── web.php                   # Trasy web
├── resources/
│   └── views/
│       └── invoices/             # Szablony faktur PDF
├── tests/
│   ├── Feature/                  # Testy funkcjonalne (OrderController, WebhookController)
│   └── Unit/                     # Testy jednostkowe (InventoryService)
├── docker-compose.yml            # Konfiguracja Docker
├── Dockerfile                     # Obraz PHP-FPM
└── nginx/                       # Konfiguracja Nginx
```

## 🔌 API Endpoints

### Autentykacja

```
POST   /api/auth/register          - Rejestracja użytkownika
POST   /api/auth/login             - Logowanie
POST   /api/auth/logout            - Wylogowanie (wymaga autoryzacji)
GET    /api/auth/google/redirect   - Przekierowanie do Google OAuth
GET    /api/auth/google/callback   - Callback Google OAuth
```

### Produkty

```
GET    /api/products               - Lista produktów (publiczne)
GET    /api/products/{id}          - Szczegóły produktu (publiczne)
POST   /api/admin/products         - Utworzenie produktu (admin, wymaga autoryzacji)
PUT    /api/admin/products/{id}    - Aktualizacja produktu (admin, wymaga autoryzacji)
DELETE /api/admin/products/{id}    - Usunięcie produktu (admin, wymaga autoryzacji)
```

### Koszyk

```
GET    /api/cart                   - Pobranie koszyka (wymaga autoryzacji)
POST   /api/cart/items             - Dodanie produktu do koszyka (wymaga autoryzacji)
PUT    /api/cart/items/{id}        - Aktualizacja pozycji koszyka (wymaga autoryzacji)
DELETE /api/cart/items/{id}        - Usunięcie pozycji z koszyka (wymaga autoryzacji)
DELETE /api/cart                   - Wyczyszczenie koszyka (wymaga autoryzacji)
```

### Zamówienia

```
GET    /api/orders                 - Lista zamówień użytkownika (wymaga autoryzacji)
POST   /api/orders                 - Utworzenie zamówienia (wymaga autoryzacji)
GET    /api/orders/{id}            - Szczegóły zamówienia (wymaga autoryzacji)
```

### Kody rabatowe

```
GET    /api/coupons                - Lista kuponów (publiczne)
GET    /api/coupons/{code}         - Szczegóły kuponu (publiczne)
POST   /api/coupons/validate       - Walidacja kuponu (wymaga autoryzacji)
```

### Webhooks Stripe

```
POST   /api/webhooks/stripe        - Webhook Stripe (weryfikacja sygnatury)
```

## 🗄 Model bazy danych

### Główne tabele

- **users** - Użytkownicy (z integracją Cashier)
- **products** - Produkty z informacjami o stanie magazynowym
- **carts** - Koszyki zakupowe użytkowników
- **cart_items** - Pozycje w koszyku
- **orders** - Zamówienia
- **order_items** - Pozycje zamówienia
- **coupons** - Kody rabatowe
- **payments** - Płatności Stripe
- **subscriptions** - Subskrypcje (Cashier)
- **subscription_items** - Pozycje subskrypcji

## 🧪 Testy

### Uruchomienie testów

```bash
# Wszystkie testy
docker-compose exec app php artisan test

# Tylko testy funkcjonalne
docker-compose exec app php artisan test --testsuite=Feature

# Tylko testy jednostkowe
docker-compose exec app php artisan test --testsuite=Unit

# Z pokryciem kodu
docker-compose exec app php artisan test --coverage
```

### Struktura testów

- **Feature Tests** - Testy integracyjne API z mockowaniem Stripe
- **Unit Tests** - Testy jednostkowe serwisów (InventoryService)
- **Mocking** - Użycie Mockery do mockowania zewnętrznych API
- **Database Transactions** - Transakcje DB w testach dla izolacji

## 🐳 Usługi Docker

- **app** - Kontener PHP-FPM 8.3 (port 9000)
- **webserver** - Kontener Nginx Alpine (port 80)
- **db** - Kontener MySQL 8.0 (port 3306)

## 💾 Dostęp do bazy danych

```
Host: localhost
Port: 3306
Database: laravel
Username: laravel
Password: secret
Root Password: root
```

## 📝 Przydatne komendy

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

# Uruchomienie migracji
docker-compose exec app php artisan migrate

# Uruchomienie seeders
docker-compose exec app php artisan db:seed

# Czyszczenie cache
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear

# Formatowanie kodu (Laravel Pint)
docker-compose exec app ./vendor/bin/pint
```

## 🔧 Konfiguracja

### Zmienne środowiskowe (.env)

```env
# Aplikacja
APP_NAME="Laravel E-commerce"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Baza danych
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret

# Stripe (płatności)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Google OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost/api/auth/google/callback

# Kolejki
QUEUE_CONNECTION=database
```

## 🛡 Bezpieczeństwo

- **Sanctum Token Authentication** - Autoryzacja API przez tokeny
- **Webhook Signature Verification** - Weryfikacja sygnatur Stripe webhooków
- **Input Validation** - Walidacja wszystkich danych wejściowych
- **SQL Injection Protection** - Ochrona przez Eloquent ORM
- **XSS Protection** - Escapowanie danych w widokach
- **CSRF Protection** - Ochrona przed atakami CSRF
- **Rate Limiting** - Ograniczenie liczby requestów

## 🔄 Workflow płatności

1. Użytkownik dodaje produkty do koszyka
2. Tworzy zamówienie (`POST /api/orders`)
3. System rezerwuje stan magazynowy (pessimistic locking)
4. Tworzony jest Stripe Payment Intent
5. Frontend obsługuje płatność przez Stripe.js
6. Webhook Stripe (`payment_intent.succeeded`) potwierdza płatność
7. System potwierdza zamówienie i zmniejsza stan magazynowy
8. Generowana jest faktura PDF (asynchronicznie przez kolejki)

## 🐛 Rozwiązywanie problemów

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

### Problem z kolejkami

Aby przetwarzać zadania w kolejce:

```bash
docker-compose exec app php artisan queue:work
```

### Problem z migracjami

Jeśli masz problemy z migracjami, możesz je przywrócić:

```bash
docker-compose exec app php artisan migrate:fresh
docker-compose exec app php artisan migrate:refresh
```

## 📚 Dokumentacja dodatkowa

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Cashier](https://laravel.com/docs/cashier)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Stripe API Documentation](https://stripe.com/docs/api)
- [Docker Documentation](https://docs.docker.com/)

## 👥 Autorzy

Projekt stworzony przez gmaxsoft.

## 📄 Licencja

Projekt jest otwartoźródłowy i dostępny na licencji MIT.

## 🔗 Linki

- [Repozytorium GitHub](https://github.com/gmaxsoft/Laravel_Ecomerce_Backend)
- [Docker Hub](https://hub.docker.com/)

## 📈 Status projektu

✅ **Projekt ukończony** - Wszystkie funkcjonalności zostały zaimplementowane i przetestowane.

### Zrealizowane funkcjonalności

- ✅ Modele z relacjami i soft deletes
- ✅ Autentykacja (Breeze + Socialite Google)
- ✅ Laravel Cashier (Stripe Payment Intents)
- ✅ API Resources i Controllers
- ✅ Events/Listeners i Queues (PDF faktury)
- ✅ System rabatów (Coupons)
- ✅ Zarządzanie stanem magazynowym (Inventory Management)
- ✅ Webhooks Stripe
- ✅ Testy z mockowaniem i transakcjami DB

---

**Gotowe do użycia w produkcji!** 🚀
