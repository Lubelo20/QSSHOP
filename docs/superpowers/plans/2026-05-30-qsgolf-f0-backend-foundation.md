# QSGOLF F0 — Backend Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Put the existing static QSGOLF storefront behind a Laravel + MySQL backend, moving the product catalog out of a hardcoded JS array into the database behind a read-only JSON API, with zero visible change to the site.

**Architecture:** A single Laravel app serves the existing static site from `public/` (same URLs) and exposes `/api/*` JSON endpoints from MySQL (same origin, no CORS). The front-end's `products.js` is rewritten to fetch the catalog from the API and fill the existing `PRODUCTS`/`CATEGORIES` arrays in place, so all current page logic keeps working with a single added `await loadProducts()`.

**Tech Stack:** Laravel 11.x (PHP 8.2+), MySQL 8.0+, Composer 2.x, vanilla-JS ES modules (existing front-end), PHPUnit (Laravel feature tests on in-memory SQLite).

**Spec:** `docs/superpowers/specs/2026-05-30-qsgolf-f0-backend-foundation-design.md`

---

## File Structure

**Created (backend):**
- `app/Models/Category.php` — Eloquent model for categories
- `app/Models/Product.php` — Eloquent model for products (JSON casts, category relation)
- `database/migrations/*_create_categories_table.php`
- `database/migrations/*_create_products_table.php`
- `database/seeders/CategorySeeder.php` — ports the 5 `CATEGORIES`
- `database/seeders/ProductSeeder.php` — ports the 14 `PRODUCTS`
- `app/Http/Resources/ProductResource.php` — maps a Product to the exact front-end JSON shape
- `app/Http/Resources/CategoryResource.php` — maps a Category to `{slug,name,emoji,desc}`
- `app/Http/Controllers/Api/ProductController.php` — index + show
- `app/Http/Controllers/Api/CategoryController.php` — index
- `routes/api.php` — API route definitions
- `tests/Feature/ProductApiTest.php`, `tests/Feature/CategoryApiTest.php`, `tests/Feature/HomeRouteTest.php`

**Modified:**
- `bootstrap/app.php` — register `routes/api.php`
- `app/Providers/AppServiceProvider.php` — `JsonResource::withoutWrapping()`
- `database/seeders/DatabaseSeeder.php` — call the two seeders
- `.env` / `phpunit.xml` — MySQL for runtime, in-memory SQLite for tests
- `routes/web.php` — `/` serves `public/index.html`

**Moved into `public/` (unchanged content):** all `*.html`, `css/`, `js/`, `components/`.

**Rewritten (front-end data source only):** `public/js/products.js`.

**Touched (one `await` each):** `public/index.html`, `public/apparel.html`, `public/drinkware.html`, `public/gift-sets.html`, `public/golf-accessories.html`, `public/stationery.html`, `public/product.html`, `public/cart.html`, `public/checkout.html`.

---

## Task 0: Preflight environment gate (no code)

**This is a gate, not a coding task. Do not proceed to Task 1 until every check passes.**

- [ ] **Step 1: Verify PHP**

Run: `php -v`
Expected: version `8.2` or higher. If missing/old → STOP, tell the user to run `brew install php`.

- [ ] **Step 2: Verify Composer**

Run: `composer --version`
Expected: Composer `2.x`. If missing → STOP, tell the user to run `brew install composer`.

- [ ] **Step 3: Verify MySQL client + server**

Run: `mysql --version`
Expected: `8.0` or higher. If missing → STOP, tell the user to run `brew install mysql && brew services start mysql`.

- [ ] **Step 4: Confirm MySQL server is reachable and create the database**

Ask the user to run (in their own shell, with their MySQL credentials):
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS qsgolf CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```
Expected: command returns with no error. Record the MySQL username/password for `.env` in Task 2.

- [ ] **Step 5: Record versions**

Note the three versions in the implementation log. All present and at/above minimum → proceed to Task 1.

---

## Task 1: Scaffold Laravel and relocate the static front-end

**Files:** creates the full Laravel skeleton at the repo root; moves static assets into `public/`.

- [ ] **Step 1: Create a fresh Laravel app in a temp directory**

Run:
```bash
composer create-project laravel/laravel ~/Downloads/qsgolf-tmp
```
Expected: Laravel installs successfully; ends with "Application ready!".

- [ ] **Step 2: Move the static front-end into the new app's `public/`**

Run:
```bash
cd ~/Downloads/QS-Golf-Shop
mv index.html apparel.html cart.html checkout.html drinkware.html gift-sets.html \
   golf-accessories.html my-account.html order-confirmed.html product.html stationery.html \
   css js components ~/Downloads/qsgolf-tmp/public/
```
Expected: those files/dirs are gone from the repo root and now live under `~/Downloads/qsgolf-tmp/public/`.

- [ ] **Step 3: Merge the Laravel app into the repo (preserving `.git` and `docs/`)**

Run:
```bash
rsync -a --exclude='.git' ~/Downloads/qsgolf-tmp/ ~/Downloads/QS-Golf-Shop/
rm -rf ~/Downloads/qsgolf-tmp
```
Expected: repo root now contains `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `public/` (with the static site inside), plus the preserved `docs/` and `.git`. Laravel's `.gitignore` (which already ignores `/vendor`, `.env`) replaces the placeholder one.

- [ ] **Step 4: Verify the static files landed in `public/`**

Run: `ls public/index.html public/css/styles.css public/js/products.js public/components/nav.html`
Expected: all four paths exist.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "chore: scaffold Laravel app and relocate static front-end into public/"
```

---

## Task 2: Configure database, API routing, test DB, and resource wrapping

**Files:**
- Modify: `.env`
- Modify: `phpunit.xml`
- Modify: `bootstrap/app.php`
- Create: `routes/api.php`
- Modify: `app/Providers/AppServiceProvider.php`

- [ ] **Step 1: Point `.env` at MySQL**

In `.env`, set (replace credentials with those recorded in Task 0):
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=qsgolf
DB_USERNAME=root
DB_PASSWORD=your_mysql_password
```

- [ ] **Step 2: Make tests use in-memory SQLite (no MySQL needed for tests)**

In `phpunit.xml`, inside `<php>`, ensure these two lines are present and uncommented:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- [ ] **Step 3: Register the API routes file**

In `bootstrap/app.php`, add the `api:` argument to `withRouting(...)`:
```php
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```

- [ ] **Step 4: Create `routes/api.php`**

```php
<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{sku}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
```

- [ ] **Step 5: Disable the JSON `data` wrapper so the API returns bare arrays**

In `app/Providers/AppServiceProvider.php`, add the import and the `boot()` body:
```php
use Illuminate\Http\Resources\Json\JsonResource;

public function boot(): void
{
    JsonResource::withoutWrapping();
}
```

- [ ] **Step 6: Verify the connection (run Laravel's default migrations)**

Run: `php artisan migrate`
Expected: PASS — default Laravel tables (`users`, `cache`, `jobs`, …) created against the `qsgolf` MySQL database with no connection error.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "chore: configure MySQL, API routing, SQLite test DB, unwrapped JSON resources"
```

---

## Task 3: Categories table and model

**Files:**
- Create: `database/migrations/2026_05_30_000001_create_categories_table.php`
- Create: `app/Models/Category.php`
- Create: `tests/Feature/CategoryModelTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/CategoryModelTest.php`:
```php
<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a category can be created and read back', function () {
    $cat = Category::create([
        'slug' => 'apparel',
        'name' => 'Apparel',
        'emoji' => '👕',
        'description' => 'Premium branded golf shirts, caps and outerwear for your team.',
        'sort_order' => 1,
    ]);

    expect($cat->fresh()->name)->toBe('Apparel');
    expect(Category::count())->toBe(1);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=CategoryModelTest`
Expected: FAIL — "Class 'App\Models\Category' not found" (or missing table).

- [ ] **Step 3: Create the migration**

`database/migrations/2026_05_30_000001_create_categories_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('emoji')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

- [ ] **Step 4: Create the model**

`app/Models/Category.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['slug', 'name', 'emoji', 'description', 'sort_order'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=CategoryModelTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: add categories table and Category model"
```

---

## Task 4: Products table and model

**Files:**
- Create: `database/migrations/2026_05_30_000002_create_products_table.php`
- Create: `app/Models/Product.php`
- Create: `tests/Feature/ProductModelTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/ProductModelTest.php`:
```php
<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a product stores json features/variants and belongs to a category', function () {
    $cat = Category::create([
        'slug' => 'apparel', 'name' => 'Apparel', 'emoji' => '👕',
        'description' => 'x', 'sort_order' => 1,
    ]);

    $product = Product::create([
        'sku' => 'QS-APP-001',
        'name' => 'QS Golf Shirt — Dark Green',
        'category_id' => $cat->id,
        'price' => 580,
        'orig_price' => 750,
        'emoji' => '👕',
        'badge' => 'Popular',
        'stock' => 18,
        'image' => 'https://example.com/shirt.jpg',
        'description' => 'A shirt.',
        'features' => ['Moisture wicking fabric', 'UV protection built-in'],
        'variants' => ['sizes' => ['S', 'M', 'L', 'XL', 'XXL']],
        'sort_order' => 1,
    ]);

    $fresh = $product->fresh();
    expect($fresh->features)->toBe(['Moisture wicking fabric', 'UV protection built-in']);
    expect($fresh->variants)->toBe(['sizes' => ['S', 'M', 'L', 'XL', 'XXL']]);
    expect($fresh->category->name)->toBe('Apparel');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ProductModelTest`
Expected: FAIL — "Class 'App\Models\Product' not found" (or missing table).

- [ ] **Step 3: Create the migration**

`database/migrations/2026_05_30_000002_create_products_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->unsignedInteger('orig_price');
            $table->string('emoji')->nullable();
            $table->string('badge')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->json('variants')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

- [ ] **Step 4: Create the model**

`app/Models/Product.php`:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku', 'name', 'category_id', 'price', 'orig_price', 'emoji',
        'badge', 'stock', 'image', 'description', 'features', 'variants', 'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'variants' => 'array',
        'price' => 'integer',
        'orig_price' => 'integer',
        'stock' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=ProductModelTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: add products table and Product model with json casts"
```

---

## Task 5: Seed the 5 categories and 14 products

**Files:**
- Create: `database/seeders/CategorySeeder.php`
- Create: `database/seeders/ProductSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Create: `tests/Feature/SeederTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/SeederTest.php`:
```php
<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeders load 5 categories and 14 products', function () {
    $this->seed();

    expect(Category::count())->toBe(5);
    expect(Product::count())->toBe(14);

    $shirt = Product::where('sku', 'QS-APP-001')->first();
    expect($shirt->name)->toBe('QS Golf Shirt — Dark Green');
    expect($shirt->category->slug)->toBe('apparel');
    expect($shirt->variants)->toBe(['sizes' => ['S', 'M', 'L', 'XL', 'XXL']]);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=SeederTest`
Expected: FAIL — counts are 0 (seeders not implemented).

- [ ] **Step 3: Create the CategorySeeder**

`database/seeders/CategorySeeder.php`:
```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'apparel', 'name' => 'Apparel', 'emoji' => '👕', 'description' => 'Premium branded golf shirts, caps and outerwear for your team.', 'sort_order' => 1],
            ['slug' => 'drinkware', 'name' => 'Drinkware', 'emoji' => '🥤', 'description' => 'Insulated tumblers and water bottles, branded for your event.', 'sort_order' => 2],
            ['slug' => 'golf-accessories', 'name' => 'Golf Accessories', 'emoji' => '⛳', 'description' => 'Branded balls, towels, shoe bags, umbrellas and more.', 'sort_order' => 3],
            ['slug' => 'gift-sets', 'name' => 'Gift Sets', 'emoji' => '🎁', 'description' => 'Curated premium gift boxes and corporate starter packs.', 'sort_order' => 4],
            ['slug' => 'stationery', 'name' => 'Stationery & Branding', 'emoji' => '📓', 'description' => 'Branded notebooks and pen sets for a complete event kit.', 'sort_order' => 5],
        ];

        foreach ($categories as $c) {
            Category::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
```

- [ ] **Step 4: Create the ProductSeeder**

`database/seeders/ProductSeeder.php` (ports the 14 products verbatim; `category` is the category slug, resolved to `category_id`):
```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $ship = 'Ships in 5–7 business days';
        $catId = Category::pluck('id', 'slug');

        $products = [
            ['sku' => 'QS-APP-001', 'name' => 'QS Golf Shirt — Dark Green', 'category' => 'apparel', 'price' => 580, 'orig_price' => 750, 'emoji' => '👕', 'badge' => 'Popular', 'stock' => 18, 'image' => 'https://images.unsplash.com/photo-1598032895397-b9472444bf93?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => ['sizes' => ['S','M','L','XL','XXL']], 'features' => ['Moisture wicking fabric','UV protection built-in','Custom branding included',$ship], 'description' => 'Our flagship dark green golf shirt, crafted from premium moisture-wicking fabric. Ideal for corporate golf days, client gifts, and team branded events across South Africa.'],
            ['sku' => 'QS-APP-002', 'name' => 'QS Golf Shirt — White', 'category' => 'apparel', 'price' => 580, 'orig_price' => 750, 'emoji' => '👔', 'badge' => null, 'stock' => 22, 'image' => 'https://images.unsplash.com/photo-1618354691373-d851c5c3a990?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => ['sizes' => ['S','M','L','XL','XXL']], 'features' => ['Moisture wicking fabric','UV protection built-in','Custom branding included',$ship], 'description' => 'Classic white golf shirt with clean lines and premium finish. Perfect for a crisp, professional look on and off the course.'],
            ['sku' => 'QS-APP-003', 'name' => 'QS Golf Shirt — Grey', 'category' => 'apparel', 'price' => 580, 'orig_price' => 750, 'emoji' => '🧥', 'badge' => null, 'stock' => 15, 'image' => 'https://images.unsplash.com/photo-1620799140408-edc6dcb6d633?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => ['sizes' => ['S','M','L','XL','XXL']], 'features' => ['Moisture wicking fabric','UV protection built-in','Custom branding included',$ship], 'description' => 'Sophisticated grey golf shirt offering a neutral, versatile base for any corporate branding. Pairs well with all team colour schemes.'],
            ['sku' => 'QS-APP-004', 'name' => 'QS Premium Golf Cap', 'category' => 'apparel', 'price' => 320, 'orig_price' => 420, 'emoji' => '🧢', 'badge' => 'Best Seller', 'stock' => 3, 'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['One size adjustable','Embroidered QS logo','UV protection',$ship], 'description' => 'The most popular item in our range. An adjustable, premium golf cap with embroidered QS branding — a staple for every corporate golf day kit.'],
            ['sku' => 'QS-DRK-001', 'name' => 'QS Insulated Tumbler', 'category' => 'drinkware', 'price' => 290, 'orig_price' => 380, 'emoji' => '🥤', 'badge' => 'Sale', 'stock' => 25, 'image' => 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['Double-wall insulation','Keeps drinks cold 12hrs / hot 8hrs','Laser-engraved branding',$ship], 'description' => 'Premium stainless steel insulated tumbler with laser-engraved QS branding. A practical, high-perceived-value gift for any corporate event.'],
            ['sku' => 'QS-DRK-002', 'name' => 'QS Water Bottle (500ml)', 'category' => 'drinkware', 'price' => 220, 'orig_price' => 280, 'emoji' => '💧', 'badge' => null, 'stock' => 30, 'image' => 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['BPA-free 500ml bottle','Leak-proof lid','Screen-printed branding',$ship], 'description' => 'Lightweight 500ml water bottle, perfect for the course. BPA-free with a secure leak-proof lid and prominent QS screen print.'],
            ['sku' => 'QS-GLF-001', 'name' => 'QS Branded Golf Balls (3-pack)', 'category' => 'golf-accessories', 'price' => 380, 'orig_price' => 480, 'emoji' => '⛳', 'badge' => 'New', 'stock' => 40, 'image' => 'https://images.unsplash.com/photo-1587174486073-ae5e5cff23aa?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['Pack of 3 premium golf balls','Custom QS logo print','Tournament quality',$ship], 'description' => 'Three tournament-quality golf balls with custom QS logo printing. A classic inclusion in any corporate golf day goodie bag.'],
            ['sku' => 'QS-GLF-002', 'name' => 'QS Branded Golf Balls (6-pack)', 'category' => 'golf-accessories', 'price' => 720, 'orig_price' => 880, 'emoji' => '⛳', 'badge' => null, 'stock' => 35, 'image' => 'https://images.unsplash.com/photo-1593111774240-d529f12cf4bb?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['Pack of 6 premium golf balls','Custom QS logo print','Tournament quality',$ship], 'description' => 'Six tournament-quality golf balls in a branded sleeve. Ideal for Standard and Premium corporate packages or as a standalone client gift.'],
            ['sku' => 'QS-GLF-003', 'name' => 'QS Premium Golf Towel', 'category' => 'golf-accessories', 'price' => 180, 'orig_price' => 240, 'emoji' => '🏌️', 'badge' => null, 'stock' => 28, 'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['Microfibre construction','Clip attachment for bag','Embroidered QS branding',$ship], 'description' => 'Plush microfibre golf towel with a sturdy bag-clip attachment. Embroidered QS logo adds a premium finish to this everyday course essential.'],
            ['sku' => 'QS-GLF-004', 'name' => 'QS Golf Shoe Bag', 'category' => 'golf-accessories', 'price' => 160, 'orig_price' => 210, 'emoji' => '👜', 'badge' => null, 'stock' => 20, 'image' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => ['colours' => ['Dark Green','White']], 'features' => ['Breathable mesh construction','QS logo print','Available in green or white',$ship], 'description' => 'A practical, well-made shoe bag in breathable mesh fabric. Available in dark green and white to match any team colour scheme.'],
            ['sku' => 'QS-GLF-005', 'name' => 'QS Golf Umbrella', 'category' => 'golf-accessories', 'price' => 480, 'orig_price' => 620, 'emoji' => '☂️', 'badge' => null, 'stock' => 5, 'image' => 'https://images.unsplash.com/photo-1519331379826-f10be5486c6f?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['Extra-large 68" canopy','Fibreglass wind-resistant frame','QS branding on panels',$ship], 'description' => 'A large, wind-resistant golf umbrella with QS branding across the canopy. A premium gift that ensures your brand is visible in all conditions.'],
            ['sku' => 'QS-GFT-001', 'name' => 'QS Premium Gift Box', 'category' => 'gift-sets', 'price' => 950, 'orig_price' => 1200, 'emoji' => '🎁', 'badge' => 'Top Pick', 'stock' => 12, 'image' => 'https://images.unsplash.com/photo-1549465220-1a8b9238cd48?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['Golf balls (3-pack)','Golf towel','Tee set','Premium branded box',$ship], 'description' => 'A beautifully presented gift box containing QS branded golf balls, a premium towel, and a tee set — packaged in a premium branded box. Perfect client gift.'],
            ['sku' => 'QS-GFT-002', 'name' => 'QS Corporate Starter Pack', 'category' => 'gift-sets', 'price' => 1650, 'orig_price' => 2100, 'emoji' => '🏆', 'badge' => 'Corporate', 'stock' => 8, 'image' => 'https://images.unsplash.com/photo-1607344645866-009c320b63e0?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => ['sizes' => ['S','M','L','XL','XXL']], 'features' => ['Golf shirt (your size)','Golf cap','Golf balls (3-pack)','Tumbler','Premium branded packaging',$ship], 'description' => 'The complete starter pack — shirt, cap, branded balls, and a tumbler all in one premium package. The go-to choice for impressing clients and rewarding staff.'],
            ['sku' => 'QS-STN-001', 'name' => 'QS Notebook & Pen Set', 'category' => 'stationery', 'price' => 140, 'orig_price' => 190, 'emoji' => '📓', 'badge' => null, 'stock' => 45, 'image' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=600&h=600&fit=crop&auto=format&q=80', 'variants' => null, 'features' => ['A5 hardcover notebook','Matching branded pen','QS logo on cover',$ship], 'description' => 'An A5 hardcover notebook and matching pen, both bearing QS branding. A stylish, practical addition to any corporate golf day package.'],
        ];

        foreach ($products as $i => $p) {
            $slug = $p['category'];
            unset($p['category']);
            $p['category_id'] = $catId[$slug];
            $p['sort_order'] = $i + 1;
            Product::updateOrCreate(['sku' => $p['sku']], $p);
        }
    }
}
```

- [ ] **Step 5: Wire the seeders into DatabaseSeeder**

Replace the body of `database/seeders/DatabaseSeeder.php`'s `run()`:
```php
public function run(): void
{
    $this->call([
        CategorySeeder::class,
        ProductSeeder::class,
    ]);
}
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --filter=SeederTest`
Expected: PASS (5 categories, 14 products, shirt assertions hold).

- [ ] **Step 7: Seed the real MySQL database**

Run: `php artisan migrate:fresh --seed`
Expected: tables rebuilt and seeded; no errors.

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat: seed 5 categories and 14 products from legacy catalog"
```

---

## Task 6: Categories API endpoint

**Files:**
- Create: `app/Http/Resources/CategoryResource.php`
- Create: `app/Http/Controllers/Api/CategoryController.php`
- Create: `tests/Feature/CategoryApiTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/CategoryApiTest.php`:
```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('GET /api/categories returns all categories in sort order with the legacy shape', function () {
    $this->seed();

    $res = $this->getJson('/api/categories');

    $res->assertOk();
    $res->assertJsonCount(5);
    // bare array (no "data" wrapper)
    expect($res->json()[0])->toHaveKeys(['slug', 'name', 'emoji', 'desc']);
    expect($res->json()[0]['slug'])->toBe('apparel');
    expect($res->json()[4]['slug'])->toBe('stationery');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=CategoryApiTest`
Expected: FAIL — 404/route or controller missing.

- [ ] **Step 3: Create the resource**

`app/Http/Resources/CategoryResource.php`:
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'desc' => $this->description,
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

`app/Http/Controllers/Api/CategoryController.php`:
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(
            Category::orderBy('sort_order')->get()
        );
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=CategoryApiTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: add GET /api/categories endpoint"
```

---

## Task 7: Products API endpoints

**Files:**
- Create: `app/Http/Resources/ProductResource.php`
- Create: `app/Http/Controllers/Api/ProductController.php`
- Create: `tests/Feature/ProductApiTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/ProductApiTest.php`:
```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('GET /api/products returns 14 products in the exact legacy shape', function () {
    $this->seed();

    $res = $this->getJson('/api/products');

    $res->assertOk();
    $res->assertJsonCount(14);

    $first = collect($res->json())->firstWhere('sku', 'QS-APP-001');
    expect($first)->toMatchArray([
        'id' => 'QS-APP-001',
        'sku' => 'QS-APP-001',
        'name' => 'QS Golf Shirt — Dark Green',
        'category' => 'Apparel',
        'slug' => 'apparel',
        'price' => 580,
        'origPrice' => 750,
        'badge' => 'Popular',
        'stock' => 18,
    ]);
    expect($first['variants'])->toBe(['sizes' => ['S', 'M', 'L', 'XL', 'XXL']]);
    expect($first['features'])->toContain('Moisture wicking fabric');
});

test('GET /api/products/{sku} returns one product', function () {
    $this->seed();

    $res = $this->getJson('/api/products/QS-DRK-001');

    $res->assertOk();
    expect($res->json('name'))->toBe('QS Insulated Tumbler');
    expect($res->json('id'))->toBe('QS-DRK-001');
});

test('GET /api/products/{sku} returns 404 for an unknown sku', function () {
    $this->seed();

    $this->getJson('/api/products/NOPE-999')->assertNotFound();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=ProductApiTest`
Expected: FAIL — route/controller missing.

- [ ] **Step 3: Create the resource (exact legacy JSON shape)**

`app/Http/Resources/ProductResource.php`:
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->sku,
            'sku' => $this->sku,
            'name' => $this->name,
            'category' => $this->category->name,
            'slug' => $this->category->slug,
            'price' => $this->price,
            'origPrice' => $this->orig_price,
            'emoji' => $this->emoji,
            'badge' => $this->badge,
            'stock' => $this->stock,
            'image' => $this->image,
            'variants' => $this->variants,
            'features' => $this->features,
            'description' => $this->description,
        ];
    }
}
```

- [ ] **Step 4: Create the controller**

`app/Http/Controllers/Api/ProductController.php`:
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return ProductResource::collection(
            Product::with('category')->orderBy('sort_order')->get()
        );
    }

    public function show(string $sku)
    {
        $product = Product::with('category')->where('sku', $sku)->firstOrFail();

        return new ProductResource($product);
    }
}
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=ProductApiTest`
Expected: PASS (all three tests).

- [ ] **Step 6: Run the full backend suite**

Run: `php artisan test`
Expected: PASS — all feature tests green.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat: add GET /api/products and /api/products/{sku} endpoints"
```

---

## Task 8: Serve the storefront at `/`

**Files:**
- Modify: `routes/web.php`
- Create: `tests/Feature/HomeRouteTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Feature/HomeRouteTest.php`:
```php
<?php

test('the root URL serves the storefront index.html', function () {
    $res = $this->get('/');

    $res->assertOk();
    $res->assertSee('Shop All Products', false);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=HomeRouteTest`
Expected: FAIL — default route returns the Laravel welcome view, not "Shop All Products".

- [ ] **Step 3: Replace the root route**

In `routes/web.php`, replace the default `Route::get('/', ...)` with:
```php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->file(public_path('index.html'));
});
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=HomeRouteTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat: serve storefront index.html at root URL"
```

---

## Task 9: Rewrite `products.js` to load the catalog from the API

**Files:**
- Modify (full rewrite of data source): `public/js/products.js`

> Front-end automated tests are intentionally deferred per the spec (no JS test runner in this codebase yet). This task is verified by manual checks with exact expected output.

- [ ] **Step 1: Rewrite `public/js/products.js`**

Replace the entire file with:
```js
// Catalog is loaded once from the API and cached in these arrays.
// loadProducts() fills them IN PLACE so existing `import { PRODUCTS }`
// references stay valid after the first await.
export const PRODUCTS = [];
export const CATEGORIES = [];

let loadPromise = null;

export function loadProducts() {
  if (loadPromise) return loadPromise;
  loadPromise = (async () => {
    const [pRes, cRes] = await Promise.all([
      fetch('/api/products'),
      fetch('/api/categories'),
    ]);
    if (!pRes.ok || !cRes.ok) throw new Error('Failed to load catalog');
    const products = await pRes.json();
    const categories = await cRes.json();
    PRODUCTS.splice(0, PRODUCTS.length, ...products);
    CATEGORIES.splice(0, CATEGORIES.length, ...categories);
  })();
  return loadPromise;
}

export function getProductById(id) {
  return PRODUCTS.find(p => p.id === id) || null;
}

export function getProductsByCategory(slug) {
  return PRODUCTS.filter(p => p.slug === slug);
}

export function getFeatured(n = 6) {
  return PRODUCTS.filter(p => p.badge).slice(0, n);
}
```

- [ ] **Step 2: Start the server**

Run: `php artisan serve`
Expected: serving on `http://127.0.0.1:8000`.

- [ ] **Step 3: Verify the API directly**

Run: `curl -s http://127.0.0.1:8000/api/products | head -c 200`
Expected: a JSON array (starts with `[{"id":"QS-APP-001"`...), NOT wrapped in `{"data":...}`.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat: load catalog from API in products.js, filling PRODUCTS in place"
```

---

## Task 10: Make pages await the catalog before first render

**Files (each gets one `await loadProducts()` added; `import` line extended to include `loadProducts`):**
- Modify: `public/index.html`
- Modify: `public/apparel.html`
- Modify: `public/drinkware.html`
- Modify: `public/gift-sets.html`
- Modify: `public/golf-accessories.html`
- Modify: `public/stationery.html`
- Modify: `public/product.html`
- Modify: `public/cart.html`
- Modify: `public/checkout.html`

> All page `<script type="module">` blocks support top-level `await`.

- [ ] **Step 1: index.html**

Change the import to add `loadProducts`, and await before the first `render()`:
```js
import { PRODUCTS, loadProducts } from './js/products.js';
```
Then immediately before the existing `render();` call (the one after the event listeners are attached), insert:
```js
await loadProducts();
```

- [ ] **Step 2: The 5 category pages (apparel, drinkware, gift-sets, golf-accessories, stationery)**

Each has an identical structure. In each file change:
```js
import { PRODUCTS } from './js/products.js';
```
to:
```js
import { PRODUCTS, loadProducts } from './js/products.js';
```
and insert immediately before the existing `render();` line:
```js
await loadProducts();
```

- [ ] **Step 3: product.html**

Change:
```js
import { getProductById, PRODUCTS } from './js/products.js';
```
to:
```js
import { getProductById, PRODUCTS, loadProducts } from './js/products.js';
```
Then await the catalog BEFORE the product lookup. Change:
```js
const params = new URLSearchParams(location.search);
const id = params.get('id');
const product = id ? getProductById(id) : null;
```
to:
```js
const params = new URLSearchParams(location.search);
const id = params.get('id');
await loadProducts();
const product = id ? getProductById(id) : null;
```

- [ ] **Step 4: cart.html — warm the cache before rendering**

`cart.js` resolves products from `PRODUCTS`, so the cache must be loaded first. In cart.html's module script, add `loadProducts` import and await it before the first `render()`. Add this import near the top of the script block:
```js
import { loadProducts } from './js/products.js';
```
and insert immediately before the final `render();` call:
```js
await loadProducts();
```

- [ ] **Step 5: checkout.html — warm the cache before rendering**

In checkout.html's module script, add near the existing imports:
```js
import { loadProducts } from './js/products.js';
```
and insert immediately before the final `updateSidebar();` call:
```js
await loadProducts();
```

- [ ] **Step 6: Manual smoke test (server running via `php artisan serve`)**

Open a browser and verify each, expected result in parentheses:
- `http://127.0.0.1:8000/` (shop-all grid shows 14 products; category/sort filters work)
- `http://127.0.0.1:8000/apparel.html` (4 apparel products)
- `http://127.0.0.1:8000/drinkware.html` (2 drinkware products)
- `http://127.0.0.1:8000/golf-accessories.html` (5 products)
- `http://127.0.0.1:8000/gift-sets.html` (2 products)
- `http://127.0.0.1:8000/stationery.html` (1 product)
- `http://127.0.0.1:8000/product.html?id=QS-APP-001` (detail loads, sizes shown, related products shown, tabs work)
- Add a product to cart → badge increments; `http://127.0.0.1:8000/cart.html` lists it with correct name/price; promo `GOLF20` applies 20% off
- `http://127.0.0.1:8000/checkout.html` (sidebar shows cart items + total)

Expected: every page behaves exactly as before F0, now sourced from the API. No console errors.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat: await catalog load on all catalog-consuming pages"
```

---

## Task 11: Final verification and wrap-up

- [ ] **Step 1: Run the full backend test suite**

Run: `php artisan test`
Expected: PASS — all feature tests green (Category/Product model, Seeder, Category API, Product API, Home route).

- [ ] **Step 2: Confirm the success criteria from the spec**

Verify, and record in the implementation log:
1. Catalog served from MySQL via `/api/products`, `/api/products/{sku}`, `/api/categories`.
2. Every page renders identically to before, sourced from the API.
3. Backend tests pass; manual smoke checklist (Task 10 Step 6) passed.
4. `php artisan serve` runs the site locally; all work committed.

- [ ] **Step 3: Final commit (if anything outstanding)**

```bash
git add -A
git commit -m "chore: F0 backend foundation complete"
```

---

## Self-Review Notes

- **Spec coverage:** Architecture (Task 1, 2, 8) · data model categories/products with JSON columns (Task 3, 4) · seeding 14+5 (Task 5) · API shape contract (Task 6, 7) · front-end Approach-A changes (Task 9, 10) · backend tests + manual smoke (Tasks 3–7 tests, Task 10 Step 6) · preflight (Task 0) · git init already done during brainstorming. All spec sections map to tasks.
- **Out of scope honored:** no auth, no server cart, no payments, no admin UI, no uploads — none appear in tasks.
- **Type/name consistency:** `loadProducts`, `PRODUCTS`, `CATEGORIES`, `getProductById`, `getProductsByCategory`, `getFeatured` are used identically across Tasks 9–10. Resource keys (`id`, `sku`, `category`, `slug`, `origPrice`, `variants`, `features`) match the test assertions in Task 7 and the front-end consumers. `category_id`, `orig_price`, `sort_order` column names are consistent across migration (Task 4), model fillable (Task 4), seeder (Task 5), and resource mapping (Task 7).
