# 🧐 Jalsah AI Appointment System

**Domain**: [https://jalsah-ai.app](https://jalsah-ai.app)  
**Connected To**: [https://jalsah.app](https://jalsah.app) via **JWT & API**

---

## 🔧 Technologies Used

- **Bootstrap** (Latest version)  
- **SweetAlert2**  
- **jQuery** (when needed)

---

## 🎯 Project Overview

Jalsah AI is a smart online mental health appointment system that helps patients find the right therapist either through a dynamic diagnosis flow powered by ChatGPT or by browsing a curated list of therapists. 

All sessions:
- **Fixed Duration: 45 minutes**  
- **Session Type: Online only**

---

## 🤩 Features Breakdown

### 🔗 Integration
- Connected to the main system (jalsah.app) via **JWT & API**
- All bookings are saved in the shared database with a special flag `AI` to identify Jalsah AI sessions

---

### 👥 Patient Features

#### ✅ Registration
- Required fields: First name, Last name, Age, Email, Phone, WhatsApp, Country (Egypt > Arab countries > alphabetical others), Password
- Email verification required before login
- Resend activation code allowed after 1 minute
- If user already exists, missing fields will be updated only

#### 🔐 Login
- Token-based login only
- Access to platform restricted without login

#### 🏠 Homepage (After Login)
1. **Smart Matching via ChatGPT**
   - Personalized conversation using patient name
   - Outputs diagnosis from a predefined list
   - Re-diagnose button available
2. **Browse Therapists**
   - Filter by: Top Rated, Lowest Price, Earliest Slot
   - Therapist card includes:
     - Name, photo, specialty
     - Certificate gallery (lightbox)
     - “Why this therapist is right for your diagnosis” (custom text)
     - Session price, next available slot, ranking if applicable

#### 📅 Appointments Page
- Shows only Jalsah AI sessions (past & upcoming)
- A session is marked as “past” when the therapist marks attendance or ends session (from `session_action` table)

#### 🗓 Booking System
- Shows available days → click to see 45-min slots
- Booking Cart System:
  - One therapist per cart
  - Switching therapists prompts user to clear cart
  - Toggle slot in/out of cart with one click (no reload)
- Cart Page:
  - Each session displayed in a separate table with therapist name, session type, date, time, fees
  - Coupon field (integration TBD)
  - Booking confirmation creates WooCommerce order (status: pending)
  - Session info saved in JSON under `meta_key`, flagged with `from_jalsah_ai = true`
  - Patient not associated with sessions until payment is completed
  - 15-minute timer displayed, link emailed and shown to user
  - Unpaid bookings are canceled after timeout
  - Webhook from WooCommerce clears cart after successful payment

---

### 🧑‍⚕️ Therapist Display

- Therapist visibility on Jalsah AI controlled by toggle in their profile
- Each therapist can be linked to one or more diagnoses
  - Each diagnosis includes:
    - Admin-assigned rating
    - Custom text explaining fit
    - Sort order and inclusion in recommendations
  - Separate image for AI site (optional)
  - Custom profit % per therapist (first session vs follow-ups)

---

### 📋 Admin Panel

- Manage diagnoses list
- Link therapists to multiple diagnoses
- Set per-diagnosis rating and reason text
- Control number of recommended therapists per diagnosis
- Analytics:
  - Users who didn't complete AI diagnosis
  - Who diagnosed but didn’t book
  - Who booked after AI
  - Repeat bookings with same therapist
  - Retention rate leaderboard
- “Switch User” to view any patient dashboard from admin

---

### 💬 ChatGPT API Integration

- Controlled via prompt and predefined diagnoses
- Patient first name sent to personalize flow
- Returns a specific diagnosis → fetch matching therapists
- Fully manageable via admin panel (prompts, diagnoses, linking)

---

### 📲 WhatsApp Cloud API Integration

- Instant notifications for:
  - Patient: Booking confirmation, schedule changes, reminders (22h & 1h before), session start
  - Therapist: New AI booking, schedule changes, prescription requests

---

### 📥 Coupon System (TBD)

- Create exclusive coupons for selected users
- Discounts apply to platform fee only
- Per-user or global usage limits
- Coupon stored in order metadata like jalsah.app

---

### 🧾 Financials & Payments

- Therapist earns only after confirming attendance
- Payout = custom % of session price (not full amount)
- Transaction log clearly states source: “AI Booking”

---

### 🤭 Navigation

Header includes:
- Homepage
- My Appointments
- Profile
- Logout

---

### 📮 Additional Notes

- AI sessions marked with green badge labeled “AI”
- Edit/postpone buttons hidden for AI sessions
- Therapists can upload/send files to patient
- One free reschedule allowed up to 24h in advance
- If patient no-shows >15 minutes, session can't be started
- After session, therapist selects attendance status to trigger payout

---

### ✅ Summary

Jalsah AI is a next-gen extension of jalsah.app, built to make mental health access smarter, more personalized, and fully powered by AI-assisted diagnosis and dynamic therapist matching. It offers both patients and admins a seamless and secure experience.

