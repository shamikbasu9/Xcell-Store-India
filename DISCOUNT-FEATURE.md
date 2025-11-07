# Discount Price Feature

## Overview
Added discount pricing functionality to show original price, discounted price, and discount percentage across the entire website.

## Setup Instructions

### 1. Run the Database Migration
Visit: `http://localhost/Xcell%20Store%20India/add-discount-price.php`

This will add the `discount_price` column to the products table.

### 2. Add Products with Discounts
- Go to Admin Panel → Products → Add Product
- Enter **Original Price** (required)
- Enter **Discount Price** (optional - leave empty if no discount)
- The discount price must be less than the original price

## Features Added

### Admin Panel
- **Product Add Form**: New "Discount Price" field
- Validates that discount price is less than original price
- Optional field - leave empty for no discount

### User-Facing Pages

#### 1. Products Listing Page
- Shows discount percentage badge (e.g., "25% OFF")
- Displays discounted price prominently
- Shows original price with strikethrough
- Falls back to regular price if no discount

#### 2. Product Detail Page
- Large discount percentage badge
- Discounted price in large text
- Original price with strikethrough
- Shows "You save ₹X!" message
- Views counter displayed alongside price

#### 3. Homepage (Featured Products)
- Discount badge on featured items
- Discounted price display
- Original price strikethrough

#### 4. Shopping Cart
- Shows discounted price for cart items
- Original price with strikethrough
- Cart total automatically uses discount prices

#### 5. Related Products
- Discount badges and pricing on related items

## Helper Functions Added

### `calculateDiscountPercentage($originalPrice, $discountPrice)`
Calculates the discount percentage rounded to nearest whole number.

### `getProductPrice($product)`
Returns the effective price (discount price if available, otherwise original price).

### `getCartTotal()` (Updated)
Now uses discount prices when calculating cart totals.

## Display Format

**With Discount:**
```
[25% OFF]
₹749.00  ₹999.00
```

**Without Discount:**
```
₹999.00
```

## Database Schema
```sql
ALTER TABLE products 
ADD COLUMN discount_price DECIMAL(10, 2) NULL AFTER price;
```

## Files Modified
1. `/includes/functions.php` - Added helper functions
2. `/admin/product-add.php` - Added discount field to form
3. `/products.php` - Display discount on listing
4. `/product-detail.php` - Display discount on detail page
5. `/index.php` - Display discount on homepage
6. `/cart.php` - Display discount in cart
7. Database migration script created

## Notes
- Discount price is optional
- System automatically validates discount < original price
- All cart calculations use discounted prices
- Checkout and order processing will use the effective price
