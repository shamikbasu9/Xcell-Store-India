# 🌱 Xcell Store India - Plant Selling Website

A complete, responsive online plant selling eCommerce platform built with PHP, MySQL, and MDBootstrap.

## ✨ Features

### 🔹 User Side (Frontend)
- **Authentication**: Signup, Login, Password Reset
- **Landing Page**: Hero section, featured plants, testimonials
- **Product Browsing**: Search, filter by category/price, grid/card layouts
- **Product Details**: Images, care instructions, related products
- **Shopping Cart**: Add/remove items, quantity management
- **Checkout**: Multiple payment gateways (Razorpay/Stripe/PayPal)
- **Coupon System**: Apply discount codes
- **Order Management**: Track orders, view history, download invoices
- **Support**: Contact form, FAQ page
- **Responsive Design**: Mobile-first with bottom navigation

### 🔹 Admin Side (Backend)
- **Dashboard**: Sales analytics, revenue reports, statistics
- **Product Management**: Add/edit/delete plants with images
- **Category Management**: Organize plants by type
- **Order Management**: Track and update order status
- **User Management**: View users, block/unblock accounts
- **Coupon System**: Create and manage discount codes
- **Support Management**: Respond to customer queries
- **Settings**: Payment gateway config, tax rates, shipping

## 🚀 Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser

### Setup Steps

1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start Apache and MySQL

2. **Install Database**
   - Open browser: `http://localhost/Xcell%20Store%20India/install.php`
   - This will automatically create the database and tables

3. **Default Admin Credentials**
   - Email: `admin@xcellstore.com`
   - Password: `admin123`

4. **Access Points**
   - User Site: `http://localhost/Xcell%20Store%20India/`
   - Admin Panel: `http://localhost/Xcell%20Store%20India/admin/`

## 📁 Project Structure

```
Xcell Store India/
├── admin/                  # Admin dashboard
│   ├── includes/          # Admin header/footer
│   ├── index.php          # Dashboard with statistics
│   ├── products.php       # Product management
│   ├── product-add.php    # Add new product
│   ├── categories.php     # Category management
│   ├── orders.php         # Order management
│   ├── users.php          # User management
│   ├── coupons.php        # Coupon management
│   ├── support.php        # Support tickets
│   ├── settings.php       # Site settings
│   ├── login.php          # Admin login
│   └── logout.php         # Admin logout
├── auth/                  # Authentication
│   ├── login.php          # User login
│   ├── signup.php         # User registration
│   ├── logout.php         # User logout
│   ├── forgot-password.php
│   └── reset-password.php
├── config/                # Configuration
│   ├── config.php         # Site configuration
│   └── database.php       # Database connection
├── database/              # Database
│   └── schema.sql         # Database schema
├── includes/              # Shared components
│   ├── header.php         # Site header with navigation
│   ├── footer.php         # Site footer
│   └── functions.php      # Helper functions
├── uploads/               # Uploaded files
│   └── products/          # Product images
├── index.php              # Homepage
├── products.php           # Product listing
├── product-detail.php     # Product details
├── cart.php               # Shopping cart
├── cart-action.php        # Cart operations (AJAX)
├── checkout.php           # Checkout page
├── process-order.php      # Order processing
├── order-success.php      # Order confirmation
├── orders.php             # User orders
├── order-detail.php       # Order details
├── profile.php            # User profile
├── faq.php                # FAQ page
├── contact.php            # Contact form
├── install.php            # Installation script
└── README.md              # Documentation
```

## 🎨 Design Features

### Desktop View
- Professional navbar with logo and navigation
- Grid layout for products (3-4 per row)
- Sidebar navigation in admin panel
- Responsive scaling for all screen sizes

### Mobile View
- Native app-like interface
- Top AppBar with logo and menu
- Bottom Navigation Bar (Home, Plants, Cart, Profile)
- Full-width cards with large touch-friendly buttons
- Vertical scrollable product list

### Theme
- **Colors**: Nature-inspired green tones (#2e7d32, #66bb6a)
- **Dark Mode**: Toggle between light and dark themes
- **Typography**: Inter font family
- **Icons**: FontAwesome 6.5.1
- **Framework**: MDBootstrap 7.1.0

## 💳 Payment Gateways

Configure in Admin > Settings:
- **Razorpay** (Default for India)
- **Stripe** (International)
- **PayPal** (International)

## 🔧 Configuration

### Database Settings
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'xcell_store_india');
```

### Site Settings
Configure via Admin Panel > Settings:
- Site Name
- Currency (INR/USD/EUR)
- Tax Rate (GST %)
- Shipping Charges
- Free Shipping Threshold
- Payment Gateway API Keys

## 📊 Database Schema

### Main Tables
- **users** - Customer accounts
- **admins** - Admin accounts with roles
- **products** - Plant products
- **product_images** - Product images
- **categories** - Product categories
- **orders** - Customer orders
- **order_items** - Order line items
- **addresses** - Shipping addresses
- **coupons** - Discount codes
- **support_tickets** - Customer support
- **faqs** - Frequently asked questions
- **settings** - Site configuration

## 🛡️ Security Features

- Password hashing (bcrypt)
- SQL injection prevention (prepared statements)
- XSS protection (input sanitization)
- Session management
- Secure file uploads
- Admin role-based access

## 📱 Responsive Breakpoints

- **Mobile**: < 768px (Bottom navigation, full-width cards)
- **Tablet**: 768px - 1024px (Adaptive grid)
- **Desktop**: > 1024px (Full navigation, sidebar)

## 🌐 Browser Support

- Chrome (recommended)
- Firefox
- Safari
- Edge
- Mobile browsers

## 📝 Default Data

### Categories
- Indoor Plants
- Outdoor Plants
- Succulents
- Flowering Plants
- Air Purifying
- Medicinal Plants

### Sample FAQs
- Plant care instructions
- Delivery information
- Return policy
- Watering guidelines
- Support contact

## 🔄 Workflow

### User Journey
1. Browse plants on homepage/products page
2. View product details with care instructions
3. Add to cart
4. Apply coupon code (optional)
5. Checkout with address
6. Select payment method
7. Place order
8. Track order status
9. View order history

### Admin Workflow
1. Login to admin panel
2. Add/manage products with images
3. Create categories
4. Process orders and update status
5. Manage users
6. Create discount coupons
7. Respond to support tickets
8. Configure site settings

## 🚀 Performance

- Optimized database queries
- Image compression recommended
- Session-based cart (no database overhead)
- Minimal JavaScript dependencies
- CDN for CSS/JS libraries

## 📧 Email Notifications

Configure SMTP for:
- Order confirmations
- Password resets
- Support ticket responses
- Order status updates

## 🔐 Admin Roles

- **Super Admin**: Full access
- **Editor**: Manage products, orders, content

## 💡 Tips

1. Change default admin password after first login
2. Configure payment gateway API keys in settings
3. Add product images for better presentation
4. Create discount coupons for promotions
5. Regularly backup database
6. Monitor orders and respond to support tickets

## 📞 Support

For issues or questions:
- Email: admin@xcellstore.com
- Create support ticket in admin panel

## 📄 License

This project is for educational and commercial use.

---

**Made with 🌿 for plant lovers in India**

**Tech Stack**: PHP, MySQL, MDBootstrap, FontAwesome
