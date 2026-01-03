# Planned Changes: Therapist Filtering and Pricing Logic

> **Note:** These changes are **planned** and have NOT been applied yet. This document is for future reference and implementation planning.

---

## 1. Diagnosis Filter (Specialization)
- **Current:** The diagnosis filter dropdown shows all diagnoses, even if no therapist is assigned to them.
- **Planned:** Only show diagnoses in the filter dropdown that have at least one therapist assigned.

## 2. Filtering vs. Sorting
- **Current:**
  - Some filters (e.g., specialization) act as true filters (hide therapists).
  - Others (price, rating, appointment) act as sorting only.
- **Planned:**
  - Diagnosis (specialization) will remain a true filter.
  - All other controls (price, rating, closest/farthest appointment) will be sorting only (never hide therapists, just change order).

## 3. Location-Based Pricing
- **Current:**
  - Pricing is determined by user location (via IP geolocation and cookies).
  - The price is selected for the user's country if available, otherwise a default price is used.
  - Currency is converted and displayed based on user location/cookie.
- **Planned:**
  - No changes planned at this time, but future improvements may include:
    - More robust fallback logic for missing country prices.
    - User ability to manually select country/currency if geolocation fails.
    - Improved UI feedback for price localization.

## 4. Code Refactoring (Planned)
- Centralize all filtering and sorting logic for maintainability.
- Add more unit tests for pricing and filtering logic.
- Improve documentation and code comments for all pricing-related functions.

---

**This file is for planning purposes only. No changes described here are currently live.** 