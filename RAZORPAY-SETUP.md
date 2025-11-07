# Razorpay Payment Integration Setup Guide

## Overview
The website now supports Razorpay payment gateway with test and live mode support.

## Features Added
- ✅ Test mode toggle
- ✅ Razorpay checkout integration
- ✅ Payment verification
- ✅ Test mode indicator on checkout page
- ✅ Secure payment handling

## Setup Instructions

### 1. Get Razorpay API Keys

#### For Test Mode:
1. Go to [Razorpay Dashboard](https://dashboard.razorpay.com/)
2. Sign up or login
3. Navigate to **Settings** → **API Keys**
4. Click **Generate Test Key**
5. Copy the **Key ID** (starts with `rzp_test_`)
6. Copy the **Key Secret**

#### For Live Mode:
1. Complete KYC verification on Razorpay
2. Activate your account
3. Navigate to **Settings** → **API Keys**
4. Click **Generate Live Key**
5. Copy the **Key ID** (starts with `rzp_live_`)
6. Copy the **Key Secret**

### 2. Update Configuration

Open `/config/config.php` and update these lines:

```php
// Test Mode Keys
define('RAZORPAY_TEST_KEY', 'rzp_test_YOUR_ACTUAL_TEST_KEY');
define('RAZORPAY_TEST_SECRET', 'YOUR_ACTUAL_TEST_SECRET');

// Live Mode Keys (when ready to go live)
define('RAZORPAY_LIVE_KEY', 'rzp_live_YOUR_ACTUAL_LIVE_KEY');
define('RAZORPAY_LIVE_SECRET', 'YOUR_ACTUAL_LIVE_SECRET');
```

### 3. Toggle Between Test and Live Mode

In `/config/config.php`, change this line:

```php
// For testing
define('RAZORPAY_TEST_MODE', true);

// For production
define('RAZORPAY_TEST_MODE', false);
```

## Test Cards (Test Mode Only)

### Successful Payment
- **Card Number:** 4111 1111 1111 1111
- **CVV:** Any 3 digits
- **Expiry:** Any future date

### Failed Payment
- **Card Number:** 4111 1111 1111 1112
- **CVV:** Any 3 digits
- **Expiry:** Any future date

### UPI Test
- **UPI ID:** success@razorpay
- **UPI ID (Failed):** failure@razorpay

## How It Works

### 1. Order Creation
- User fills checkout form
- Selects Razorpay payment method
- Order is created in database with status "pending"

### 2. Payment Processing
- Razorpay checkout modal opens
- User completes payment
- Razorpay returns payment ID and signature

### 3. Payment Verification
- System verifies payment signature
- Updates order status to "completed"
- Stores transaction ID
- Redirects to success page

### 4. Payment Cancellation
- If user cancels payment
- Order remains in "pending" status
- User can retry payment from orders page

## Payment Flow

```
User → Checkout → Razorpay Modal → Payment → Verification → Success
                                      ↓
                                   Cancel → Orders Page
```

## Security Features

- ✅ Payment signature verification
- ✅ Server-side validation
- ✅ Secure API key storage
- ✅ Transaction logging
- ✅ User authentication required

## Test Mode Indicators

When test mode is active:
- ⚠️ Warning banner on checkout page
- 🧪 "TEST" badge next to Razorpay option
- Test credentials are used automatically

## Going Live Checklist

- [ ] Complete Razorpay KYC verification
- [ ] Get live API keys
- [ ] Update `RAZORPAY_LIVE_KEY` and `RAZORPAY_LIVE_SECRET`
- [ ] Set `RAZORPAY_TEST_MODE` to `false`
- [ ] Test with real payment methods
- [ ] Monitor transactions in Razorpay dashboard

## Troubleshooting

### Payment Not Opening
- Check if Razorpay script is loaded
- Verify API key is correct
- Check browser console for errors

### Payment Verification Failed
- Verify secret key is correct
- Check signature generation logic
- Review server logs

### Test Mode Not Working
- Ensure `RAZORPAY_TEST_MODE` is `true`
- Use test cards only
- Check test API keys

## Support

For Razorpay support:
- Documentation: https://razorpay.com/docs/
- Support: https://razorpay.com/support/

## Files Modified

1. `/config/config.php` - Added Razorpay configuration
2. `/checkout.php` - Added Razorpay integration
3. `/verify-payment.php` - Payment verification handler
4. `/process-order.php` - Order creation with payment status

## Important Notes

⚠️ **Never commit API keys to version control**
⚠️ **Always use test mode for development**
⚠️ **Verify payments on server-side**
⚠️ **Keep secret keys secure**
