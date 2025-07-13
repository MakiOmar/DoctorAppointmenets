# 🧠 Jalsah AI Appointment System  
**Domain**: [https://jalsah-ai.app](https://jalsah-ai.app)  
**Connected To**: [https://jalsah.app](https://jalsah.app) via **JWT & API**

---

## 🔧 Technologies Used

- **Bootstrap** (Latest version)  
- **SweetAlert2**  
- **jQuery** (when needed)

---

## 🎯 Project Overview

Jalsah AI is a smart appointment platform where patients can either get matched to a therapist via ChatGPT diagnosis or browse therapists manually. All appointments are **online only** and have a fixed **duration of 45 minutes**.

---

## 🧩 Features Breakdown

### 🔗 Integration
- Authentication and session booking are done through secure **JWT & API** with `jalsah.app`.

---

### 👥 Patient Features

#### ✅ Registration Fields
- First name  
- Last name  
- Age  
- Email  
- Phone  
- WhatsApp  
- Country (sorted as: Egypt → Arab countries → others alphabetically)  
- Password  
- Email verification is required  
- Resend activation code after 1 minute  
- If a user already exists, only missing fields will be updated

#### 🔐 Login
- Token-based login only  
- Force login/signup before accessing the app

#### 📑 Homepage Options
1. **Smart Matching (via ChatGPT)**
   - Patient answers diagnostic questions
   - Therapists with matching diagnoses are shown
   - Patients can re-diagnose any time

2. **Browse Therapists**  
   - Filter by: highest rated, lowest price, earliest available  
   - Ratings are per diagnosis and controlled by Admin  
   - Each therapist card shows:
     - Name, photo, specialty
     - Certification gallery (lightbox)
     - "Why this therapist is good for your diagnosis"
     - Session price (45 min)
     - Earliest available time
     - Ranking number (if in recommended list)

3. **Appointments Page**
   - View past and upcoming bookings from `jalsah-ai.app`
   - A session is marked as "past" when therapist marks attendance or ends session
   - Session data retrieved from `session_action` table

4. **Booking Appointments**
   - Display available dates → select date → show 45-minute slots only
   - Add-to-cart system (not WooCommerce cart)
   - One therapist per cart – prompt to clear cart if switching
   - Booking multiple slots is allowed
   - Click to toggle slot in cart (no page reload)
   - Real-time availability check before confirming

5. **Cart Page**
   - Separate table per booking with:
     - Therapist name
     - Session type: Online
     - Duration: 45 minutes
     - Date (day & date)
     - Time
   - Apply coupon (system TBD)
   - Payment summary:
     - Therapist fees
     - Additional fees
     - Total amount
   - Click "Confirm Booking" → create WooCommerce order
     - All bookings in one order (status: `pending`)
     - Store session info as `meta_key` with JSON
     - Flag: `from_jalsah_ai = true`
     - No association with patient until payment complete

6. **Payment Process**
   - Send confirmation & payment link via email
   - Show link on cart page
   - 15-minute countdown timer
   - Cancel unpaid bookings after timeout and free slots
   - After payment success:
     - Trigger webhook to clear cart
   - If expired, patient can retry (check for slot conflicts)

---

## 🧑‍⚕️ Therapist Display

- Display therapists based on a toggle field in their profile (show/hide on jalsah-ai.app)
- Each therapist can have **multiple diagnoses** attached (admin-defined)
- For each diagnosis:
  - Admin adds rating
  - Admin adds a message: “Why this therapist is suitable for this diagnosis”
- Additional therapist info shown only in `jalsah-ai.app`:
  - Short bio (editable)
  - Certification gallery
  - Earliest slot
  - Price (from base system)

---

## ⚙️ Admin Panel (Main System)

- Add/Edit diagnosis list
- Assign multiple diagnoses per therapist
- Add ratings per therapist per diagnosis
- Control number of therapists shown in recommendations
- Add "Why this therapist fits this diagnosis" text
- New fields in user profile (only for jalsah-ai.app):
  - Show on AI site
  - Diagnosis list
  - Bio
  - Diagnosis-specific notes

---

## 🔖 Tagging System

- All appointments from jalsah-ai.app are flagged with a green circle badge labeled **"AI"**
- These flagged appointments are filtered out from the main jalsah.app UI

---

## 🧾 Future Considerations

- Coupon system needs to be reviewed (use WooCommerce vs custom plugin)
- Webhook listener for payment success needed
- Session overlapping logic already exists in main system – reuse for 45-minute constraint

---

## 📌 Navigation

Header includes:
- Home
- Appointments
- Profile
- Logout