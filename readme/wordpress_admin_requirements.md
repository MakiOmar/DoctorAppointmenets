# 📘 WordPress Admin Requirements for Jalsah AI Integration

To successfully power the Jalsah AI system and enable seamless communication with the frontend (`jalsah-ai.app`) via API and JWT, the following features, custom fields, and tools must be available in the WordPress admin (`jalsah.app`).

---

## 🧑‍⚕️ Therapist Profile (User Role: `doctor`)

### 🔘 General Fields:
- `show_on_ai_site` (boolean): Toggle to control visibility on Jalsah AI
- `ai_display_name` (string): Optional display name override for AI
- `ai_profile_image` (image): Custom image shown on AI platform
- `secretary_phone` (string): Optional for display in AI sessions

### 🔘 Diagnosis Association:
- `diagnosis_list` (multi-select): Linked diagnosis IDs
- For each diagnosis:
  - `diagnosis_rank_points` (int): Internal ranking value
  - `diagnosis_custom_message` (text): Why this therapist fits this diagnosis

### 🔘 Financial Settings:
- `ai_first_session_percentage` (float)
- `ai_followup_session_percentage` (float)

### 🔘 General Bio:
- `public_short_bio` (text): For non-AI listings on browse-all

---

## 📁 Diagnosis Management (Admin Panel)
- Create/Edit/Delete diagnoses
- Sort priority manually
- Assign diagnoses to therapists
- Set:
  - Default number of top therapists to show per diagnosis (e.g. 10)

---

## 📅 Session Management

### Session Table Flag:
- All AI bookings must be flagged with: `from_jalsah_ai = true`

### Attendance Table (`snks_sessions_actions`):
> ⚠️ This table already exists in the database with the following structure. Developers may extend it if additional fields are needed for AI attendance logic.

```sql
CREATE TABLE wpds_snks_sessions_actions (
  ID int AUTO_INCREMENT PRIMARY KEY,
  action_session_id int NOT NULL,
  case_id int NOT NULL,
  attendance varchar(3) NOT NULL
);
```

- Used to track attendance via:
  - "Did the patient attend?" → `yes` / `no`

### Admin View:
- Filter by:
  - AI sessions only
  - Attendance status
  - Diagnosis / therapist / date

---

## 🧾 WooCommerce Order Integration
- Store all AI sessions in order meta:
  - `meta_key = jalsah_ai_sessions`
  - Value: JSON of session details
  - `from_jalsah_ai = true`
  - Store applied coupon
- Sessions not linked to user until payment completes
- After payment → webhook clears cart

---

## 🧾 Coupon System
- Coupons apply to platform share only
- Usage Limits:
  - Per-user or global
  - Can expire or be unlimited
- Stored with order in metadata
- Must support segment-based distribution (e.g. specific patients)

---

## 🔐 Admin Tools
- "Switch User" feature to login as patient and see their dashboard
- Special admin login (email-only + master password)
- Filters in Users page:
  - By registration source (Jalsah AI)
  - By diagnosis association

---

## 📊 Analytics & Reporting
- Track:
  - Users who dropped before diagnosis
  - Diagnosed but didn’t book
  - Booked via AI
  - Repeat bookings per therapist
  - Session counts per user / diagnosis
- Retention Leaderboard:
  - Therapists sorted by # of patients who booked more than once

---

## 💬 ChatGPT Integration
- Prompt editor
- Send: patient name, prompt, available diagnosis list
- Receive: valid diagnosis (from system)
- Logic: fetch & rank matching therapists

---

## 📲 WhatsApp Cloud API
- Store credentials
- Template manager per event:
  - Booking confirmation
  - Reschedule alert
  - 22h + 1h reminder
  - Therapist joined session
  - Prescription requested
  - Marketing campaign

---

## 💊 Rochtah (Prescription System)

### Roles:
- New Role: `rochtah_doctor`

### Prescription Request Flow:
- Triggered from therapist session card
- Form:
  - Initial diagnosis (text)
  - Symptoms (text)
- Confirmation step required
- Sends:
  - WhatsApp + Email to patient

### Rochtah Booking:
- Available only if patient confirms
- Backend schedule config per day (20-min slots)
- Fields:
  - Available days per week
  - Time ranges per day

### Prescription Handling:
- Rochtah Doctor Dashboard
  - View: confirmed bookings
  - Fields: date, time, patient name/email, referring therapist, diagnosis
  - Write: prescription text + optional file upload
- Session hides "Start" button after prescription sent

---

## 📄 Email Notification Settings
- New AI Booking → send email (includes doctor/patient names and time)
- New AI User → send registration email
- Rochtah request → notify patient with CTA

---

## 🪪 Optional: Therapist Public Ranking Page
- Accessible to logged-in therapists
- Shows:
  - Whether they appear in AI lists
  - Rank in each diagnosis category

---

✅ This admin setup ensures that the API can respond with:
- Verified therapist lists per diagnosis
- Custom display data for AI site
- Attendance tracking
- Coupon validation
- Analytics insights
- Real-time communication (ChatGPT + WhatsApp)

Let me know if you'd like this in JSON schema or ACF layout format.

