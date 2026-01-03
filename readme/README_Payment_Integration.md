# Enhanced WooCommerce Payment Integration for Jalsah AI

## Overview

This document outlines the enhanced WooCommerce payment integration for Jalsah AI, leveraging the existing frontend cart system and adding WooCommerce order creation at checkout. This approach maximizes the benefits of both systems while maintaining the proven user experience.

## Table of Contents

1. [Integration Architecture](#integration-architecture)
2. [Product Management](#product-management)
3. [Existing Frontend Cart System](#existing-frontend-cart-system)
4. [WooCommerce Order Creation](#woocommerce-order-creation)
5. [Backend API Endpoints](#backend-api-endpoints)
6. [Frontend Integration](#frontend-integration)
7. [Payment Flow](#payment-flow)
8. [Order Processing](#order-processing)
9. [Database Schema](#database-schema)
10. [Security Considerations](#security-considerations)
11. [Testing Strategy](#testing-strategy)
12. [Deployment Checklist](#deployment-checklist)

---

## Integration Architecture

### Enhanced Hybrid Flow

**New Integration:**
```
Frontend Cart → Custom API → WooCommerce Order → Payment Gateway → Webhook → Order Completion
```

### Benefits of This Approach

1. **Leverage Existing System**: Use proven frontend cart functionality
2. **Gateway Flexibility**: Easy switching between payment gateways
3. **Risk Mitigation**: Multiple payment options for business continuity
4. **Proven Infrastructure**: WooCommerce handles payment complexity
5. **Admin Control**: Non-technical staff can manage payments
6. **Standard Compliance**: WooCommerce ensures security standards
7. **Better Performance**: No WooCommerce cart overhead

---

## Product Management

### Automatic Product Creation

#### 1. Plugin Activation Hook
```php
// functions/ai-integration.php
register_activation_hook(__FILE__, 'snks_create_ai_products');

function snks_create_ai_products() {
    // Create AI Session Product
    $ai_session_product = new WC_Product_Simple();
    $ai_session_product->set_name('جلسة علاج نفسي - AI');
    $ai_session_product->set_description('جلسة علاج نفسي عبر منصة جلسة AI - مدة الجلسة 45 دقيقة');
    $ai_session_product->set_short_description('جلسة علاج نفسي عبر الإنترنت');
    $ai_session_product->set_regular_price('0'); // Default price 0, will be overridden dynamically
    $ai_session_product->set_virtual(true); // Virtual product
    $ai_session_product->set_downloadable(false);
    $ai_session_product->set_status('publish');
    $ai_session_product->set_catalog_visibility('hidden'); // Hidden from catalog
    $ai_session_product->set_sold_individually(false);
    
    $product_id = $ai_session_product->save();
    
    // Store product ID in options
    update_option('snks_ai_session_product_id', $product_id);
    
    return $product_id;
}
```

#### 2. Product Helper Functions
```php
// functions/helpers/ai-products.php
class SNKS_AI_Products {
    
    public static function get_ai_session_product_id() {
        $product_id = get_option('snks_ai_session_product_id');
        
        if (!$product_id || !wc_get_product($product_id)) {
            // Recreate product if it doesn't exist
            $product_id = snks_create_ai_products();
        }
        
        return $product_id;
    }
}
```

---

## Existing Frontend Cart System

### Current Implementation

The system already has a complete frontend cart system:

#### 1. Vue.js Cart Store (`jalsah-ai-frontend/src/stores/cart.js`)
```javascript
export const useCartStore = defineStore('cart', () => {
  const cartItems = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Computed properties
  const totalPrice = computed(() => {
    return cartItems.value.reduce((total, item) => total + 200.00, 0) // Default price
  })

  const itemCount = computed(() => cartItems.value.length)

  // Actions
  const loadCart = async (userId) => {
    // Load cart from existing API endpoint
    const response = await api.get(`/wp-json/jalsah-ai/v1/get-user-cart`, {
      params: { user_id: userId }
    })
    
    if (response.data.success) {
      cartItems.value = response.data.data || []
    }
  }

  const addToCart = async (appointmentData) => {
    // Add to cart using existing API endpoint
    const response = await api.post('/wp-json/jalsah-ai/v1/add-appointment-to-cart', appointmentData)
    
    if (response.data.success) {
      await loadCart(appointmentData.user_id)
      return { success: true }
    }
  }

  const removeFromCart = async (slotId, userId) => {
    // Remove from cart using existing API endpoint
    const response = await api.post('/wp-json/jalsah-ai/v1/remove-from-cart', {
      slot_id: slotId,
      user_id: userId
    })
    
    if (response.data.success) {
      await loadCart(userId)
      return { success: true }
    }
  }

  // Enhanced checkout function
  const checkout = async (userId) => {
    loading.value = true
    error.value = null
    
    try {
      // Create WooCommerce order from existing cart
      const response = await api.post('/wp-json/jalsah-ai/v1/create-woocommerce-order', {
        user_id: userId,
        cart_items: cartItems.value
      })
      
      if (response.data.success) {
        // Redirect to WooCommerce checkout
        window.location.href = response.data.checkout_url
        return { success: true, checkout_url: response.data.checkout_url }
      } else {
        error.value = response.data.error || 'Failed to create order'
        return { success: false, message: error.value }
      }
    } catch (err) {
      error.value = 'Failed to checkout'
      console.error('Error during checkout:', err)
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  return {
    cartItems,
    loading,
    error,
    totalPrice,
    itemCount,
    loadCart,
    addToCart,
    removeFromCart,
    checkout,
    clearCart
  }
})
```

#### 2. Existing Backend API Endpoints
- `POST /wp-json/jalsah-ai/v1/add-appointment-to-cart` - Add appointment to cart
- `GET /wp-json/jalsah-ai/v1/get-user-cart` - Get user's cart contents
- `POST /wp-json/jalsah-ai/v1/remove-from-cart` - Remove item from cart
- `POST /wp-json/jalsah-ai/v1/book-appointments-from-cart` - Direct booking (to be replaced)

---

## WooCommerce Order Creation

### 1. New API Endpoint for WooCommerce Order Creation
```php
// functions/ai-integration.php
register_rest_route( 'jalsah-ai/v1', '/create-woocommerce-order', array(
    'methods' => 'POST',
    'callback' => array( $this, 'create_woocommerce_order_from_cart' ),
    'permission_callback' => '__return_true',
) );

public function create_woocommerce_order_from_cart($request) {
    $user_id = $request->get_param('user_id');
    $cart_items = $request->get_param('cart_items');
    
    if (!$user_id || !$cart_items) {
        return new WP_REST_Response(['error' => 'Missing user_id or cart_items'], 400);
    }
    
    try {
        // Create WooCommerce order from existing cart
        $order = SNKS_AI_Orders::create_order_from_existing_cart($user_id, $cart_items);
        
        return new WP_REST_Response([
            'success' => true,
            'order_id' => $order->get_id(),
            'checkout_url' => $order->get_checkout_payment_url(),
            'total' => $order->get_total(),
            'appointments_count' => count($cart_items)
        ]);
        
    } catch (Exception $e) {
        return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
}
```

### 2. Order Creation Helper Class
```php
// functions/helpers/ai-orders.php
class SNKS_AI_Orders {
    
    public static function create_order_from_existing_cart($user_id, $cart_items) {
        if (empty($cart_items)) {
            throw new Exception('No appointments in cart');
        }
        
        // Get user data
        $user = get_userdata($user_id);
        if (!$user) {
            throw new Exception('User not found');
        }
        
        // Create WooCommerce order
        $order = wc_create_order();
        
        // Add appointments as order items
        foreach ($cart_items as $cart_item) {
            $product_id = SNKS_AI_Products::get_ai_session_product_id();
            
            $item = new WC_Order_Item_Product();
            $item->set_props([
                'name' => sprintf(
                    'جلسة علاج نفسي - %s - %s %s',
                    get_the_title($cart_item['therapist_id']),
                    $cart_item['date'],
                    $cart_item['time']
                ),
                'quantity' => 1,
                'total' => $cart_item['price'] ?? 200.00, // Use price from cart or default
                'subtotal' => $cart_item['price'] ?? 200.00,
                'product_id' => $product_id
            ]);
            
            // Add appointment metadata
            $item->add_meta_data('therapist_id', $cart_item['therapist_id']);
            $item->add_meta_data('session_date', $cart_item['date']);
            $item->add_meta_data('session_time', $cart_item['time']);
            $item->add_meta_data('session_duration', 45);
            $item->add_meta_data('is_ai_session', true);
            $item->add_meta_data('slot_id', $cart_item['slot_id']);
            
            $order->add_item($item);
        }
        
        // Set customer data
        $order->set_billing_email($user->user_email);
        $order->set_billing_first_name($user->display_name);
        $order->set_billing_phone(get_user_meta($user_id, 'phone', true));
        $order->set_customer_id($user_id);
        
        // Add AI-specific metadata
        $order->update_meta_data('from_jalsah_ai', true);
        $order->update_meta_data('ai_user_id', $user_id);
        $order->update_meta_data('ai_appointments_count', count($cart_items));
        $order->update_meta_data('ai_total_amount', $order->get_total());
        
        // Set payment method (will be selected at checkout)
        $order->set_payment_method(''); // Let user select at checkout
        $order->set_payment_method_title('');
        
        $order->save();
        
        return $order;
    }
    
    public static function process_ai_order_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order || $order->get_meta('from_jalsah_ai') !== 'true') {
            return false;
        }
        
        // Create appointments from order items
        foreach ($order->get_items() as $item) {
            $therapist_id = $item->get_meta('therapist_id');
            $session_date = $item->get_meta('session_date');
            $session_time = $item->get_meta('session_time');
            $slot_id = $item->get_meta('slot_id');
            
            if ($therapist_id && $session_date && $session_time) {
                // Update existing slot to booked status
                self::book_appointment_slot($slot_id, $order_id, $order->get_customer_id());
                
                // Link appointment to order item
                $item->add_meta_data('appointment_id', $slot_id);
                $item->save();
            }
        }
        
        // Clear user's AI cart
        delete_user_meta($order->get_customer_id(), 'ai_cart');
        
        // Send notifications
        self::send_ai_order_notifications($order_id);
        
        return true;
    }
    
    private static function book_appointment_slot($slot_id, $order_id, $patient_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $wpdb->prefix . 'snks_provider_timetable',
            [
                'session_status' => 'open',
                'order_id' => $order_id,
                'settings' => 'ai_booking:completed'
            ],
            ['ID' => $slot_id],
            ['%s', '%d', '%s'],
            ['%d']
        );
        
        return $result;
    }
}
```

---

## Backend API Endpoints

### 1. Create WooCommerce Order from Cart
**Endpoint:** `POST /wp-json/jalsah-ai/v1/create-woocommerce-order`

**Request Body:**
```json
{
  "user_id": 456,
  "cart_items": [
    {
      "slot_id": 123,
      "therapist_id": 789,
      "therapist_name": "د. أحمد محمد",
      "date": "2024-03-15",
      "time": "14:00:00",
      "price": 200.00
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "order_id": 101,
  "checkout_url": "https://yoursite.com/checkout/order-pay/101/?pay_for_order=true&key=wc_order_abc123",
  "total": 200.00,
  "appointments_count": 1
}
```

### 2. Existing Cart Endpoints (Unchanged)
- `POST /wp-json/jalsah-ai/v1/add-appointment-to-cart`
- `GET /wp-json/jalsah-ai/v1/get-user-cart`
- `POST /wp-json/jalsah-ai/v1/remove-from-cart`

---

## Frontend Integration

### 1. Enhanced Cart Store
```javascript
// src/stores/cart.js - Modified checkout function
const checkout = async (userId) => {
  loading.value = true
  error.value = null
  
  try {
    // Create WooCommerce order from existing cart
    const response = await api.post('/wp-json/jalsah-ai/v1/create-woocommerce-order', {
      user_id: userId,
      cart_items: cartItems.value
    })
    
    if (response.data.success) {
      // Redirect to WooCommerce checkout
      window.location.href = response.data.checkout_url
      return { success: true, checkout_url: response.data.checkout_url }
    } else {
      error.value = response.data.error || 'Failed to create order'
      return { success: false, message: error.value }
    }
  } catch (err) {
    error.value = 'Failed to checkout'
    console.error('Error during checkout:', err)
    return { success: false, message: error.value }
  } finally {
    loading.value = false
  }
}
```

### 2. Enhanced Checkout Component
```vue
<!-- src/views/Cart.vue - Modified checkout button -->
<template>
  <div class="checkout-container">
    <!-- Existing cart items display -->
    
    <!-- Payment Summary -->
    <div class="payment-summary">
      <h3>{{ $t('checkout.paymentSummary') }}</h3>
      <div class="summary-item">
        <span>{{ $t('checkout.subtotal') }}</span>
        <span>{{ formatCurrency(cartStore.totalPrice) }}</span>
      </div>
      <div class="summary-item total">
        <span>{{ $t('checkout.total') }}</span>
        <span>{{ formatCurrency(cartStore.totalPrice) }}</span>
      </div>
    </div>

    <!-- Payment Button -->
    <button 
      @click="proceedToPayment" 
      :disabled="cartStore.loading || cartStore.itemCount === 0"
      class="payment-button"
    >
      <span v-if="cartStore.loading">{{ $t('checkout.processing') }}</span>
      <span v-else>{{ $t('checkout.proceedToPayment') }} {{ formatCurrency(cartStore.totalPrice) }}</span>
    </button>
  </div>
</template>

<script setup>
import { useCartStore } from '../stores/cart'
import { useAuthStore } from '../stores/auth'

const cartStore = useCartStore()
const authStore = useAuthStore()

const proceedToPayment = async () => {
  try {
    const result = await cartStore.checkout(authStore.user.id)
    
    if (!result.success) {
      // Handle error (already handled in store)
      return
    }
    
    // Redirect happens automatically in the store
  } catch (error) {
    console.error('Payment error:', error)
  }
}
</script>
```

---

## Payment Flow

### 1. Cart Management (Existing)
```
User adds appointments → Frontend Cart Store → Existing API → Cart stored in user meta
```

### 2. Order Creation (New)
```
User clicks checkout → Create WooCommerce Order → Add cart items as order items → Redirect to WooCommerce checkout
```

### 3. Payment Processing (WooCommerce)
```
WooCommerce checkout → Payment gateway → Webhook → Order completion → Create appointments
```

### 4. Order Completion (Enhanced)
```
Payment success → Process order items → Update appointment slots → Clear cart → Send notifications
```

---

## Order Processing

### WooCommerce Hooks Integration
```php
// functions/ai-integration.php

// Process AI orders on payment completion
add_action('woocommerce_payment_complete', 'snks_process_ai_order_payment');
add_action('woocommerce_order_status_changed', 'snks_process_ai_order_status_change', 10, 3);

function snks_process_ai_order_payment($order_id) {
    $order = wc_get_order($order_id);
    
    if ($order->get_meta('from_jalsah_ai') === 'true') {
        SNKS_AI_Orders::process_ai_order_payment($order_id);
    }
}

function snks_process_ai_order_status_change($order_id, $old_status, $new_status) {
    if (in_array($new_status, ['completed', 'processing'])) {
        $order = wc_get_order($order_id);
        
        if ($order->get_meta('from_jalsah_ai') === 'true') {
            SNKS_AI_Orders::process_ai_order_payment($order_id);
        }
    }
}

// Customize WooCommerce checkout for AI orders
add_filter('woocommerce_checkout_fields', 'snks_customize_ai_checkout_fields');
add_action('woocommerce_checkout_order_processed', 'snks_handle_ai_checkout_order', 10, 3);

function snks_customize_ai_checkout_fields($fields) {
    // Check if current order is from Jalsah AI
    $order_id = get_query_var('order-pay');
    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_meta('from_jalsah_ai') === 'true') {
            // Customize fields for AI orders
            $fields['billing']['billing_email']['required'] = true;
            $fields['billing']['billing_phone']['required'] = true;
            
            // Add AI-specific fields if needed
            $fields['billing']['ai_user_id'] = [
                'type' => 'hidden',
                'default' => get_current_user_id()
            ];
        }
    }
    
    return $fields;
}

function snks_handle_ai_checkout_order($order_id, $posted_data, $order) {
    // Check if order contains AI sessions
    $has_ai_sessions = false;
    foreach ($order->get_items() as $item) {
        if ($item->get_meta('is_ai_session') === 'true') {
            $has_ai_sessions = true;
            break;
        }
    }
    
    if ($has_ai_sessions) {
        // Mark order as AI order
        $order->update_meta_data('from_jalsah_ai', true);
        $order->update_meta_data('ai_user_id', get_current_user_id());
        $order->save();
    }
}
```

---

## Database Schema

### Enhanced Options Table
```sql
-- Store AI product ID
INSERT INTO wp_options (option_name, option_value) VALUES
('snks_ai_session_product_id', '123');

-- Store AI settings
INSERT INTO wp_options (option_name, option_value) VALUES
('snks_ai_default_session_price', '200'),
('snks_ai_session_duration', '45');
```

### Existing Cart System (Unchanged)
- User meta table stores cart data
- Timetable table tracks appointment slots
- No additional tables needed

---

## Security Considerations

### 1. API Security
- JWT authentication for all endpoints
- Rate limiting (max 10 requests per minute per user)
- Input validation and sanitization
- CORS configuration for frontend domain

### 2. Payment Security
- WooCommerce handles payment security
- Payment gateway webhook signature verification
- HTTPS enforcement
- Payment amount validation

### 3. Data Protection
- Encrypt sensitive payment data
- Log payment activities for audit
- Implement proper error handling
- Regular security audits

---

## Testing Strategy

### 1. Unit Tests
- Product creation functions
- Order creation functions
- API endpoint validation
- Cart integration tests

### 2. Integration Tests
- End-to-end payment flow
- Multiple bookings in one order
- Cart to order conversion
- Payment gateway switching

### 3. Manual Testing
- Payment flow with test cards
- Multiple appointment booking
- Cart functionality
- Mobile responsiveness

---

## Deployment Checklist

### Pre-Deployment
- [ ] WooCommerce installed and configured
- [ ] Payment gateway configured (PayPal, Stripe, etc.)
- [ ] Existing cart system verified
- [ ] SSL certificate verification

### Deployment
- [ ] Activate plugin (creates products automatically)
- [ ] Configure payment gateways
- [ ] Test cart to order conversion
- [ ] Monitor order processing

### Post-Deployment
- [ ] Verify product creation
- [ ] Test cart checkout flow
- [ ] Monitor payment processing
- [ ] Set up payment analytics

---

## Migration from Existing System

### 1. Backward Compatibility
- Keep existing cart endpoints functional
- Maintain current user experience
- Gradual migration to new checkout

### 2. Data Migration
- No data migration needed
- Existing cart data remains intact
- New orders use WooCommerce system

### 3. User Communication
- Inform users about enhanced payment options
- Maintain familiar cart interface
- Highlight improved security

---

*This document should be updated as the integration evolves and new requirements are identified.* 